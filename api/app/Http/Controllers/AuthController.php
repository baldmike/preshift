<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * Validates that the request contains a properly formatted email and a
     * password string, then attempts credential-based authentication via the
     * Auth facade. On success, a new Sanctum personal access token named
     * "auth-token" is created and returned alongside the user model.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The authenticated user and bearer token, or a 401 error.
     */
    public function login(Request $request): JsonResponse
    {
        // Validate that email is present and well-formed, and password is a non-empty string.
        $request->validate([
            'email' => 'required|email',       // Must be a valid email address
            'password' => 'required|string',   // Must be a non-empty string
        ]);

        // Attempt to authenticate using only the email and password fields.
        // Auth::attempt checks the credentials against the users table.
        if (! Auth::attempt($request->only('email', 'password'))) {
            // Return a 401 Unauthorized response if credentials do not match.
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // Retrieve the now-authenticated user instance from the Auth guard.
        $user = Auth::user();

        // Create a new Sanctum personal access token for stateless API usage.
        // plainTextToken contains the raw token string to send back to the client.
        $token = $user->createToken('auth-token')->plainTextToken;

        // Return the user object and the plain-text token for the client to store.
        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Log the authenticated user out by revoking their current access token.
     *
     * This deletes only the token used for the current request, leaving any
     * other active tokens intact. The client should discard its stored token
     * after receiving the success response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A confirmation message.
     */
    public function logout(Request $request): JsonResponse
    {
        // Delete the Sanctum token that was used to authenticate this request.
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the currently authenticated user's profile with their location.
     *
     * Eager-loads the related Location model so the client receives the full
     * user record including location details in a single response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The authenticated user with their location relationship.
     */
    public function user(Request $request): JsonResponse
    {
        // Eager-load the 'location' relationship onto the authenticated user model.
        return response()->json($request->user()->load('location'));
    }
}
