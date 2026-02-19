<?php

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

// Auth
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Location-scoped routes
    Route::middleware('location')->group(function () {
        // Pre-Shift hero
        Route::get('/preshift', [PreShiftController::class, 'index']);

        // 86'd Board
        Route::get('/eighty-sixed', [EightySixedController::class, 'index']);
        Route::post('/eighty-sixed', [EightySixedController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/eighty-sixed/{eightySixed}/restore', [EightySixedController::class, 'restore'])->middleware('role:admin,manager');

        // Specials
        Route::get('/specials', [SpecialController::class, 'index']);
        Route::post('/specials', [SpecialController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/specials/{special}', [SpecialController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/specials/{special}', [SpecialController::class, 'destroy'])->middleware('role:admin,manager');

        // Push Items
        Route::get('/push-items', [PushItemController::class, 'index']);
        Route::post('/push-items', [PushItemController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/push-items/{pushItem}', [PushItemController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/push-items/{pushItem}', [PushItemController::class, 'destroy'])->middleware('role:admin,manager');

        // Announcements
        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('role:admin,manager');

        // Acknowledgments
        Route::post('/acknowledge', [AcknowledgmentController::class, 'store']);
        Route::get('/acknowledgments/status', [AcknowledgmentController::class, 'status']);

        // Menu Items
        Route::get('/menu-items', [MenuItemController::class, 'index']);
        Route::post('/menu-items', [MenuItemController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/menu-items/{menuItem}', [MenuItemController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy'])->middleware('role:admin,manager');

        // Categories
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store'])->middleware('role:admin,manager');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('role:admin,manager');

        // Users (manager+)
        Route::middleware('role:admin,manager')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::patch('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });
    });

    // Locations (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/locations', [LocationController::class, 'index']);
        Route::post('/locations', [LocationController::class, 'store']);
        Route::patch('/locations/{location}', [LocationController::class, 'update']);
    });
});
