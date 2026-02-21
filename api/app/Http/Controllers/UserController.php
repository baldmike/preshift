<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateMyAvailabilityRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

/**
 * UserController manages user accounts within the application.
 */
class UserController extends Controller
{
    /**
     * List users with role-based query scoping.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of user records.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin() && $request->has('location_id')) {
            $users = User::where('location_id', $request->query('location_id'))->get();
        } elseif ($user->isAdmin() && $request->boolean('all')) {
            $users = User::all();
        } else {
            $users = User::where('location_id', $user->location_id)->get();
        }

        return response()->json($users);
    }

    /**
     * Create a new user account.
     *
     * @param  \App\Http\Requests\StoreUserRequest  $request
     * @return \Illuminate\Http\JsonResponse  The newly created user with a 201 status.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $validated['password'] = Hash::make($validated['password']);

        if (empty($validated['location_id'])) {
            $validated['location_id'] = $request->user()->location_id;
        }

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    /**
     * Update an existing user account.
     *
     * @param  \App\Http\Requests\UpdateUserRequest  $request
     * @param  \App\Models\User          $user  The user to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * Update the authenticated user's own availability.
     *
     * @param  \App\Http\Requests\UpdateMyAvailabilityRequest  $request
     * @return \Illuminate\Http\JsonResponse  The updated user.
     */
    public function updateMyAvailability(UpdateMyAvailabilityRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $user->update(['availability' => $validated['availability']]);

        return response()->json($user);
    }

    /**
     * Delete a user account permanently.
     *
     * @param  \App\Models\User  $user  The user to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }
}
