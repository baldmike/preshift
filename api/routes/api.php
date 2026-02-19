<?php

/**
 * API Route Definitions
 *
 * All routes defined here are automatically prefixed with "/api" by Laravel's
 * route service provider. The file is organized into three tiers:
 *
 *   1. Public (unauthenticated) -- only the login endpoint.
 *   2. Authenticated (auth:sanctum) -- logout, current user, and all
 *      location/admin-scoped resources.
 *   3. Location-scoped (auth:sanctum + location middleware) -- the bulk of the
 *      app: pre-shift dashboard, 86'd board, specials, push items,
 *      announcements, acknowledgments, menu items, categories, and users.
 *
 * Write operations (POST/PATCH/DELETE) on most resources additionally require
 * the `role:admin,manager` middleware, restricting mutations to management.
 * Read operations (GET) are available to all authenticated staff at the
 * location so servers and bartenders can view the pre-shift information.
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PreShiftController;
use App\Http\Controllers\EightySixedController;
use App\Http\Controllers\SpecialController;
use App\Http\Controllers\PushItemController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AcknowledgmentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\CategoryController;

/*
|--------------------------------------------------------------------------
| Public Routes (no authentication required)
|--------------------------------------------------------------------------
| POST /api/login -- Accepts email + password, returns a Sanctum token and
|                    user data. This is the only endpoint accessible without
|                    a bearer token.
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Sanctum token required)
|--------------------------------------------------------------------------
| Every route inside this group requires a valid Sanctum bearer token in the
| Authorization header. The `auth:sanctum` middleware rejects unauthenticated
| requests with a 401 response.
*/
Route::middleware('auth:sanctum')->group(function () {
    // POST /api/logout -- Revokes the current access token, logging the user out.
    Route::post('/logout', [AuthController::class, 'logout']);
    // GET  /api/user   -- Returns the currently authenticated user's profile.
    Route::get('/user', [AuthController::class, 'user']);

    /*
    |----------------------------------------------------------------------
    | Location-Scoped Routes (auth:sanctum + location middleware)
    |----------------------------------------------------------------------
    | The `location` middleware (EnsureLocationAccess) reads the
    | X-Location-Id header and verifies the authenticated user either
    | belongs to that location or is an admin. It injects the resolved
    | location_id into the request so controllers can scope queries.
    | All resource endpoints below are filtered to a single location.
    */
    Route::middleware('location')->group(function () {

        // GET /api/preshift -- Aggregated pre-shift dashboard: returns today's
        //                      86'd items, active specials, push items, and
        //                      announcements in one response for the hero view.
        Route::get('/preshift', [PreShiftController::class, 'index']);

        /*
        |------------------------------------------------------------------
        | 86'd Board
        |------------------------------------------------------------------
        | Tracks menu items (or free-text items) that are currently
        | unavailable. Restoration is a PATCH (soft operation setting
        | `restored_at`) rather than a DELETE, preserving history.
        |
        | GET   /api/eighty-sixed                     -- List active 86'd items.
        | POST  /api/eighty-sixed                     -- 86 a new item (admin/manager).
        | PATCH /api/eighty-sixed/{id}/restore        -- Restore an item (admin/manager).
        */
        Route::get('/eighty-sixed', [EightySixedController::class, 'index']);
        Route::post('/eighty-sixed', [EightySixedController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/eighty-sixed/{eightySixed}/restore', [EightySixedController::class, 'restore'])->middleware('role:admin,manager');

        /*
        |------------------------------------------------------------------
        | Specials
        |------------------------------------------------------------------
        | Daily, weekly, monthly, or limited-time specials linked to a
        | menu item. Full CRUD for managers; read-only for all staff.
        |
        | GET    /api/specials              -- List active specials.
        | POST   /api/specials              -- Create a new special (admin/manager).
        | PATCH  /api/specials/{id}         -- Update a special (admin/manager).
        | DELETE /api/specials/{id}         -- Delete a special (admin/manager).
        */
        Route::get('/specials', [SpecialController::class, 'index']);
        Route::post('/specials', [SpecialController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/specials/{special}', [SpecialController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/specials/{special}', [SpecialController::class, 'destroy'])->middleware('role:admin,manager');

        /*
        |------------------------------------------------------------------
        | Push Items
        |------------------------------------------------------------------
        | Items management wants staff to upsell/suggest to guests.
        | Each has a priority (low/medium/high) and a reason.
        |
        | GET    /api/push-items            -- List active push items.
        | POST   /api/push-items            -- Create a push item (admin/manager).
        | PATCH  /api/push-items/{id}       -- Update a push item (admin/manager).
        | DELETE /api/push-items/{id}       -- Delete a push item (admin/manager).
        */
        Route::get('/push-items', [PushItemController::class, 'index']);
        Route::post('/push-items', [PushItemController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/push-items/{pushItem}', [PushItemController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/push-items/{pushItem}', [PushItemController::class, 'destroy'])->middleware('role:admin,manager');

        /*
        |------------------------------------------------------------------
        | Announcements
        |------------------------------------------------------------------
        | Manager-to-staff messages with priority levels and optional
        | role targeting (e.g., only bartenders). Can have an expiration.
        |
        | GET    /api/announcements         -- List non-expired announcements.
        | POST   /api/announcements         -- Post a new announcement (admin/manager).
        | PATCH  /api/announcements/{id}    -- Edit an announcement (admin/manager).
        | DELETE /api/announcements/{id}    -- Delete an announcement (admin/manager).
        */
        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('role:admin,manager');

        /*
        |------------------------------------------------------------------
        | Acknowledgments
        |------------------------------------------------------------------
        | Polymorphic "read receipts" that let staff confirm they have
        | seen a piece of pre-shift content (announcement, special, etc.).
        |
        | POST /api/acknowledge             -- Record an acknowledgment for the
        |                                      current user on a given resource.
        | GET  /api/acknowledgments/status  -- Check which items the current
        |                                      user has/hasn't acknowledged.
        */
        Route::post('/acknowledge', [AcknowledgmentController::class, 'store']);
        Route::get('/acknowledgments/status', [AcknowledgmentController::class, 'status']);

        /*
        |------------------------------------------------------------------
        | Menu Items
        |------------------------------------------------------------------
        | The canonical menu for the location. Each item belongs to a
        | category, has a type (food/drink/both), price, and allergen info.
        |
        | GET    /api/menu-items            -- List all menu items for the location.
        | POST   /api/menu-items            -- Add a menu item (admin/manager).
        | PATCH  /api/menu-items/{id}       -- Update a menu item (admin/manager).
        | DELETE /api/menu-items/{id}       -- Remove a menu item (admin/manager).
        */
        Route::get('/menu-items', [MenuItemController::class, 'index']);
        Route::post('/menu-items', [MenuItemController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/menu-items/{menuItem}', [MenuItemController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy'])->middleware('role:admin,manager');

        /*
        |------------------------------------------------------------------
        | Categories
        |------------------------------------------------------------------
        | Groupings for menu items (e.g., Appetizers, Entrees, Drinks).
        | Each has a sort_order for display ordering.
        |
        | GET    /api/categories            -- List categories for the location.
        | POST   /api/categories            -- Create a category (admin/manager).
        | PATCH  /api/categories/{id}       -- Update a category (admin/manager).
        | DELETE /api/categories/{id}       -- Delete a category (admin/manager).
        */
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role:admin,manager');

        /*
        |------------------------------------------------------------------
        | User Management (admin + manager only)
        |------------------------------------------------------------------
        | CRUD for staff accounts at the current location. Wrapped in a
        | role middleware group so that all four endpoints require
        | admin or manager privileges.
        |
        | GET    /api/users                 -- List staff for the location.
        | POST   /api/users                 -- Create a new staff account.
        | PATCH  /api/users/{id}            -- Update a staff account.
        | DELETE /api/users/{id}            -- Deactivate/delete a staff account.
        */
        Route::middleware('role:admin,manager')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::patch('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });
    });

    /*
    |----------------------------------------------------------------------
    | Location Management (admin only -- NOT location-scoped)
    |----------------------------------------------------------------------
    | These routes live outside the `location` middleware group because they
    | manage locations themselves rather than resources within one. Only
    | admin-role users can access them.
    |
    | GET   /api/locations              -- List all locations in the system.
    | POST  /api/locations              -- Create a new location.
    | PATCH /api/locations/{id}         -- Update a location's name/address/timezone.
    */
    Route::middleware('role:admin')->group(function () {
        Route::get('/locations', [LocationController::class, 'index']);
        Route::post('/locations', [LocationController::class, 'store']);
        Route::patch('/locations/{location}', [LocationController::class, 'update']);
    });
});
