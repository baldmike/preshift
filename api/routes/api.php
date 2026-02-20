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
use App\Http\Controllers\ShiftTemplateController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScheduleEntryController;
use App\Http\Controllers\ShiftDropController;
use App\Http\Controllers\TimeOffRequestController;
use App\Http\Controllers\ConfigController;

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
    // POST /api/change-password -- Change the authenticated user's password.
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    /*
    |----------------------------------------------------------------------
    | Config Routes (SuperAdmin)
    |----------------------------------------------------------------------
    */
    Route::get('/config/settings', [ConfigController::class, 'getSettings']);
    Route::put('/config/settings', [ConfigController::class, 'updateSettings'])->middleware('superadmin');
    Route::post('/config/reset', [ConfigController::class, 'fullReset'])->middleware('superadmin');

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
        Route::patch('/specials/{special}/decrement', [SpecialController::class, 'decrement'])->middleware('role:admin,manager');
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
        | Shift Templates
        |------------------------------------------------------------------
        | Reusable shift definitions per location (e.g. "Lunch 10:30–3:00").
        | Managers create templates once; they are referenced when building
        | weekly schedules.
        |
        | GET    /api/shift-templates         -- List templates for location.
        | POST   /api/shift-templates         -- Create a template (manager+).
        | PATCH  /api/shift-templates/{id}    -- Update a template (manager+).
        | DELETE /api/shift-templates/{id}    -- Delete a template (manager+).
        */
        Route::get('/shift-templates', [ShiftTemplateController::class, 'index']);
        Route::post('/shift-templates', [ShiftTemplateController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/shift-templates/{shiftTemplate}', [ShiftTemplateController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/shift-templates/{shiftTemplate}', [ShiftTemplateController::class, 'destroy'])->middleware('role:admin,manager');

        /*
        |------------------------------------------------------------------
        | Schedules
        |------------------------------------------------------------------
        | Weekly schedules containing shift entries. Managers create a
        | schedule for a week, populate it with entries, then publish it
        | to make shifts visible to staff.
        |
        | GET    /api/schedules               -- List current/upcoming weeks.
        | GET    /api/schedules/{id}          -- Full schedule with entries.
        | POST   /api/schedules               -- Create new week (manager+).
        | PATCH  /api/schedules/{id}          -- Update week info (manager+).
        | POST   /api/schedules/{id}/publish  -- Publish schedule (manager+).
        | POST   /api/schedules/{id}/unpublish -- Revert to draft (manager+).
        | GET    /api/my-shifts               -- Staff: my upcoming shifts.
        */
        Route::get('/schedules', [ScheduleController::class, 'index']);
        Route::get('/schedules/{schedule}', [ScheduleController::class, 'show']);
        Route::post('/schedules', [ScheduleController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/schedules/{schedule}', [ScheduleController::class, 'update'])->middleware('role:admin,manager');
        Route::post('/schedules/{schedule}/publish', [ScheduleController::class, 'publish'])->middleware('role:admin,manager');
        Route::post('/schedules/{schedule}/unpublish', [ScheduleController::class, 'unpublish'])->middleware('role:admin,manager');
        Route::get('/my-shifts', [ScheduleController::class, 'myShifts']);
        // PUT /api/my-availability -- Staff self-service: update own weekly availability grid.
        Route::put('/my-availability', [UserController::class, 'updateMyAvailability']);

        /*
        |------------------------------------------------------------------
        | Schedule Entries
        |------------------------------------------------------------------
        | Individual staff-to-shift assignments within a schedule.
        |
        | POST   /api/schedule-entries        -- Assign staff to shift (manager+).
        | PATCH  /api/schedule-entries/{id}   -- Update entry (manager+).
        | DELETE /api/schedule-entries/{id}   -- Remove entry (manager+).
        */
        Route::post('/schedule-entries', [ScheduleEntryController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/schedule-entries/{scheduleEntry}', [ScheduleEntryController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/schedule-entries/{scheduleEntry}', [ScheduleEntryController::class, 'destroy'])->middleware('role:admin,manager');

        /*
        |------------------------------------------------------------------
        | Shift Drops
        |------------------------------------------------------------------
        | Staff drop shifts they can't work; other staff volunteer to
        | pick them up; managers select the volunteer who gets the shift.
        |
        | GET    /api/shift-drops                              -- List (role-filtered).
        | POST   /api/shift-drops                              -- Drop a shift (staff).
        | POST   /api/shift-drops/{id}/volunteer               -- Volunteer to pick up (staff).
        | POST   /api/shift-drops/{id}/select/{user}           -- Manager selects volunteer.
        | POST   /api/shift-drops/{id}/cancel                  -- Cancel own drop (staff).
        */
        Route::get('/shift-drops', [ShiftDropController::class, 'index']);
        Route::post('/shift-drops', [ShiftDropController::class, 'store']);
        Route::post('/shift-drops/{shiftDrop}/volunteer', [ShiftDropController::class, 'volunteer']);
        Route::post('/shift-drops/{shiftDrop}/select/{user}', [ShiftDropController::class, 'select'])->middleware('role:admin,manager');
        Route::post('/shift-drops/{shiftDrop}/cancel', [ShiftDropController::class, 'cancel']);

        /*
        |------------------------------------------------------------------
        | Time-Off Requests
        |------------------------------------------------------------------
        | Staff submit time-off requests; managers approve or deny.
        | Approved time-off is shown in the schedule builder.
        |
        | GET    /api/time-off-requests                    -- List (role-filtered).
        | POST   /api/time-off-requests                    -- Submit request (staff).
        | POST   /api/time-off-requests/{id}/approve       -- Approve (manager+).
        | POST   /api/time-off-requests/{id}/deny          -- Deny (manager+).
        */
        Route::get('/time-off-requests', [TimeOffRequestController::class, 'index']);
        Route::post('/time-off-requests', [TimeOffRequestController::class, 'store']);
        Route::post('/time-off-requests/{timeOffRequest}/approve', [TimeOffRequestController::class, 'approve'])->middleware('role:admin,manager');
        Route::post('/time-off-requests/{timeOffRequest}/deny', [TimeOffRequestController::class, 'deny'])->middleware('role:admin,manager');

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
