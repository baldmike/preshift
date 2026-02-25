<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadProfilePhotoRequest;
use App\Http\Resources\UserResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * AuthController handles user authentication for the application.
 *
 * This controller manages login (issuing Sanctum personal access tokens),
 * logout (revoking the current token), and retrieving the authenticated
 * user's profile. It relies on Laravel Sanctum for stateless, token-based
 * API authentication.
 */
class AuthController extends Controller
{
    /**
     * Authenticate a user and issue a personal access token.
     *
     * @param  \App\Http\Requests\LoginRequest  $request
     * @return \Illuminate\Http\JsonResponse  The authenticated user and bearer token, or a 401 error.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! Auth::attempt($validated)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user'      => new UserResource($user->load(['location', 'organization'])),
            'token'     => $token,
            'locations' => $this->getLocationsForUser($user),
        ]);
    }

    /**
     * Build the locations list for a user based on their role.
     *
     * - SuperAdmin: all locations across all orgs
     * - Admin/Manager: all locations in their organization
     * - Staff: only their pivot memberships
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Support\Collection
     */
    private function getLocationsForUser($user)
    {
        if ($user->isSuperAdmin()) {
            return Location::all()->map(fn ($loc) => [
                'id'   => $loc->id,
                'name' => $loc->name,
                'role' => $user->locations()->where('location_id', $loc->id)->first()?->pivot->role ?? 'admin',
            ]);
        }

        if ($user->isAdmin() && $user->organization_id) {
            return Location::where('organization_id', $user->organization_id)->get()->map(fn ($loc) => [
                'id'   => $loc->id,
                'name' => $loc->name,
                'role' => $user->locations()->where('location_id', $loc->id)->first()?->pivot->role ?? $user->role,
            ]);
        }

        return $user->locations()->get()->map(fn ($loc) => [
            'id'   => $loc->id,
            'name' => $loc->name,
            'role' => $loc->pivot->role,
        ]);
    }

    /**
     * Log the authenticated user out by revoking their current access token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A confirmation message.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Change the authenticated user's password.
     *
     * @param  \App\Http\Requests\ChangePasswordRequest  $request
     * @return \Illuminate\Http\JsonResponse  Success message or 422 on validation/mismatch.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \App\Http\Requests\UpdateProfileRequest  $request
     * @return \Illuminate\Http\JsonResponse  The updated user with location.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->fill($validated);
        $user->save();

        return response()->json(new UserResource($user->load('location')));
    }

    /**
     * Upload a profile photo for the authenticated user.
     *
     * Deletes the old photo (if any), stores the new file at
     * `profile-photos/{user_id}.{ext}` on the public disk, and
     * updates the user's `profile_photo_path` column.
     *
     * @param  \App\Http\Requests\UploadProfilePhotoRequest  $request
     * @return \Illuminate\Http\JsonResponse  The updated user with location.
     */
    public function uploadProfilePhoto(UploadProfilePhotoRequest $request): JsonResponse
    {
        $user = $request->user();

        // Delete old photo if exists
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $ext = $request->file('photo')->getClientOriginalExtension();
        $path = $request->file('photo')->storeAs(
            'profile-photos',
            $user->id . '.' . $ext,
            'public'
        );

        $user->profile_photo_path = $path;
        $user->save();

        return response()->json(new UserResource($user->load('location')));
    }

    /**
     * Delete the authenticated user's profile photo.
     *
     * Removes the file from the public disk and sets the
     * `profile_photo_path` column to null.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The updated user with location.
     */
    public function deleteProfilePhoto(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
            $user->save();
        }

        return response()->json(new UserResource($user->load('location')));
    }

    /**
     * Return the currently authenticated user's profile with their location
     * and all establishment memberships.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The authenticated user with location and membership list.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->load(['location', 'organization']);

        return response()->json([
            'user'      => new UserResource($user),
            'locations' => $this->getLocationsForUser($user),
        ]);
    }
}
