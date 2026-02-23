<?php

namespace App\Http\Controllers;

use App\Events\BoardMessageDeleted;
use App\Events\BoardMessagePosted;
use App\Events\BoardMessageUpdated;
use App\Http\Requests\StoreBoardMessageRequest;
use App\Http\Requests\UpdateBoardMessageRequest;
use App\Http\Resources\BoardMessageResource;
use App\Models\BoardMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * BoardMessageController manages the location-scoped message board.
 *
 * The message board is an informal bulletin board where all staff can post
 * updates and replies. Posts are body-only (no title) and support one level
 * of threading. Managers can restrict visibility to managers-only and pin
 * important posts. Real-time WebSocket broadcasting notifies connected
 * clients whenever posts are created, updated, or deleted.
 */
class BoardMessageController extends Controller
{
    /**
     * List all top-level board messages for the authenticated user's location.
     *
     * Returns posts filtered by the user's role visibility, with pinned posts
     * first, then reverse chronological order. Eagerly loads author info and
     * replies with their authors.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $messages = BoardMessage::where('location_id', $user->location_id)
            ->topLevel()
            ->forVisibility($user->role)
            ->with(['user', 'replies' => function ($query) use ($user) {
                $query->forVisibility($user->role)->with('user');
            }])
            ->orderByDesc('pinned')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(BoardMessageResource::collection($messages));
    }

    /**
     * Create a new board message (top-level post or reply).
     *
     * Sets the location_id and user_id from the authenticated user. Strips
     * visibility and pinned fields for non-manager/admin users to prevent
     * staff from creating managers-only or pinned posts.
     *
     * @param  \App\Http\Requests\StoreBoardMessageRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreBoardMessageRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Staff cannot set visibility or pinned
        if ($user->isStaff()) {
            unset($validated['visibility']);
        }

        $message = BoardMessage::create([
            ...$validated,
            'location_id' => $user->location_id,
            'user_id' => $user->id,
        ]);

        $message->load('user', 'replies.user');

        broadcast(new BoardMessagePosted($message))->toOthers();

        return response()->json(new BoardMessageResource($message), 201);
    }

    /**
     * Update an existing board message.
     *
     * @param  \App\Http\Requests\UpdateBoardMessageRequest  $request
     * @param  \App\Models\BoardMessage                      $boardMessage
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateBoardMessageRequest $request, BoardMessage $boardMessage): JsonResponse
    {
        $this->authorize('update', $boardMessage);

        $validated = $request->validated();

        // Staff cannot change visibility or pinned
        if ($request->user()->isStaff()) {
            unset($validated['visibility'], $validated['pinned']);
        }

        $boardMessage->update($validated);
        $boardMessage->load('user', 'replies.user');

        broadcast(new BoardMessageUpdated($boardMessage))->toOthers();

        return response()->json(new BoardMessageResource($boardMessage));
    }

    /**
     * Delete a board message.
     *
     * Cascade deletes remove any replies when a top-level post is deleted.
     *
     * @param  \App\Models\BoardMessage  $boardMessage
     * @return \Illuminate\Http\Response
     */
    public function destroy(BoardMessage $boardMessage): Response
    {
        $this->authorize('delete', $boardMessage);

        $data = [
            'id' => $boardMessage->id,
            'parent_id' => $boardMessage->parent_id,
            'location_id' => $boardMessage->location_id,
        ];

        $boardMessage->delete();

        broadcast(new BoardMessageDeleted($data))->toOthers();

        return response()->noContent();
    }

    /**
     * Toggle the pinned status of a board message.
     *
     * Only admins and managers can pin/unpin posts.
     *
     * @param  \App\Models\BoardMessage  $boardMessage
     * @return \Illuminate\Http\JsonResponse
     */
    public function pin(BoardMessage $boardMessage): JsonResponse
    {
        $this->authorize('pin', $boardMessage);

        $boardMessage->update(['pinned' => !$boardMessage->pinned]);
        $boardMessage->load('user', 'replies.user');

        broadcast(new BoardMessageUpdated($boardMessage))->toOthers();

        return response()->json(new BoardMessageResource($boardMessage));
    }
}
