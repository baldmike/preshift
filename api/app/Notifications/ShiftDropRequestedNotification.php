<?php

namespace App\Notifications;

use App\Models\ShiftDrop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftDropRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ShiftDrop $shiftDrop)
    {
        //
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if ($notifiable->location?->email_alerts_enabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->buildData();

        return (new MailMessage)
            ->subject($data['title'])
            ->line($data['body'])
            ->action('View Shift Drops', url($data['link']));
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->buildData());
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData();
    }

    /**
     * Build the shared notification payload used by all channels.
     *
     * Includes the shift role in both the human-readable body text and as a
     * discrete `role` key so consumers can filter or style by role.
     *
     * @return array{type: string, title: string, body: string, link: string, source_id: int, role: string}
     */
    private function buildData(): array
    {
        $name = $this->shiftDrop->requester->name;
        $date = $this->shiftDrop->scheduleEntry->date;
        $role = $this->shiftDrop->scheduleEntry->role;

        return [
            'type' => 'shift_drop_requested',
            'title' => 'Shift Drop Request',
            'body' => "{$name} wants to drop their {$role} shift on {$date}.",
            'link' => '/manage/shift-drops',
            'source_id' => $this->shiftDrop->id,
            'role' => $role,
        ];
    }
}
