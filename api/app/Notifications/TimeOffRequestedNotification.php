<?php

namespace App\Notifications;

use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeOffRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TimeOffRequest $timeOffRequest)
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
            ->action('View Time Off Requests', url($data['link']));
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->buildData());
    }

    public function toArray(object $notifiable): array
    {
        return $this->buildData();
    }

    private function buildData(): array
    {
        $name = $this->timeOffRequest->user->name;
        $start = $this->timeOffRequest->start_date;
        $end = $this->timeOffRequest->end_date;

        return [
            'type' => 'time_off_requested',
            'title' => 'Time Off Request',
            'body' => "{$name} requested time off {$start} – {$end}.",
            'link' => '/manage/time-off',
            'source_id' => $this->timeOffRequest->id,
        ];
    }
}
