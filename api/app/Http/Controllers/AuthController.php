<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            'user' => $user,
            'token' => $token,
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

        return response()->json($user->load('location'));
    }

    /**
     * Return the currently authenticated user's profile with their location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The authenticated user with their location relationship.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('location'));
    }
}
