<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

/**
 * UserController manages user accounts within the application.
 *
 * This controller provides CRUD operations for user management. The index
 * method implements role-based query scoping: admins can query users across
 * locations, while non-admin users only see colleagues at their own location.
 * Password hashing is handled automatically on create and update. This
 * controller is typically restricted to admin/manager roles via route middleware.
 */
class UserController extends Controller
{
    /**
     * List users with role-based query scoping.
     *
     * Authorization logic:
     * - Admin users with a `location_id` query parameter: returns users at that specific location.
     * - Admin users with `all=true` query parameter: returns every user in the system.
     * - All other users (non-admins, or admins without special params): returns only users
     *   at the authenticated user's own location.
     *
     * This ensures non-admin staff can only see their coworkers, while admins
     * retain the ability to view and manage users across all locations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  A JSON array of user records.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin() && $request->has('location_id')) {
            // Admin requesting users for a specific location via ?location_id=N query param.
            $users = User::where('location_id', $request->query('location_id'))->get();
        } elseif ($user->isAdmin() && $request->boolean('all')) {
            // Admin requesting all users across every location via ?all=true query param.
            $users = User::all();
        } else {
            // Default: scope to the authenticated user's own location (non-admins always hit this).
            $users = User::where('location_id', $user->location_id)->get();
        }

        return response()->json($users);
    }

    /**
     * Create a new user account.
     *
     * Validates the request payload including a unique email check, hashes the
     * plaintext password using bcrypt via the Hash facade, and creates the user.
     *
     * Validation rules:
     * - name: required, string, max 255 chars -- the user's display name.
     * - email: required, valid email format, must be unique in the users table.
     * - password: required, string, minimum 8 characters.
     * - role: required, must be one of: admin, manager, server, bartender.
     * - location_id: optional FK to locations table -- the user's assigned location.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse  The newly created user with a 201 status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',               // User's display name
            'email' => 'required|email|unique:users,email',    // Must be unique across all users
            'password' => 'required|string|min:8',             // Minimum 8 characters
            'role' => 'required|in:admin,manager,server,bartender', // Role determines access level
            'location_id' => 'nullable|exists:locations,id',   // Optional assignment to a location
            'phone' => 'nullable|string|max:20',               // Contact phone number
            'availability' => 'nullable|array',                // Day-of-week availability map
            'availability.*' => 'boolean',                     // Each day value must be boolean
        ]);

        // Hash the plaintext password before storing it in the database.
        $validated['password'] = Hash::make($validated['password']);

        // Auto-assign the creating user's location if none was provided.
        if (empty($validated['location_id'])) {
            $validated['location_id'] = $request->user()->location_id;
        }

        // Create the user record with the hashed password and validated data.
        $user = User::create($validated);

        return response()->json($user, 201);
    }

    /**
     * Update an existing user account.
     *
     * Validates the same fields as store() with two differences:
     * - The email uniqueness check excludes the current user's own record
     *   (allowing them to keep their existing email unchanged).
     * - The password field is optional; if omitted, the existing password is preserved.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User          $user  The user to update (via route model binding).
     * @return \Illuminate\Http\JsonResponse  The updated user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id, // Exclude current user from uniqueness check
            'password' => 'nullable|string|min:8',                        // Optional; only update if provided
            'role' => 'required|in:admin,manager,server,bartender',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:20',                          // Contact phone number
            'availability' => 'nullable|array',                           // Day-of-week availability map
            'availability.*' => 'boolean',                                // Each day value must be boolean
        ]);

        if (isset($validated['password'])) {
            // Hash the new plaintext password if one was provided.
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // Remove the password key entirely so the existing hash is not overwritten with null.
            unset($validated['password']);
        }

        // Apply validated changes to the user record.
        $user->update($validated);

        return response()->json($user);
    }

    /**
     * Delete a user account permanently.
     *
     * Uses route model binding to resolve the User instance, then deletes it.
     * Associated records (tokens, acknowledgments, etc.) should be handled
     * by database-level cascade rules or model events.
     *
     * @param  \App\Models\User  $user  The user to delete (via route model binding).
     * @return \Illuminate\Http\Response  A 204 No Content response.
     */
    public function destroy(User $user): Response
    {
        // Permanently delete the user record from the database.
        $user->delete();

        return response()->noContent();
    }
}
