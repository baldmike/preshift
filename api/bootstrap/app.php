<?php

/**
 * Application Bootstrap Configuration
 *
 * This is the entry point for configuring the Laravel application instance.
 * It wires together routing, middleware, and exception handling using the
 * Laravel 11+ streamlined bootstrap API (replacing the older kernel classes).
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    /*
    |----------------------------------------------------------------------
    | Routing Configuration
    |----------------------------------------------------------------------
    | Registers the four route files used by the application:
    |
    |   api:      routes/api.php      -- All REST API endpoints (prefixed /api).
    |   web:      routes/web.php      -- Web routes (minimal; the app is API-driven).
    |   commands: routes/console.php  -- Artisan console commands.
    |   channels: routes/channels.php -- WebSocket channel authorization callbacks
    |                                    used by Laravel Broadcasting / Echo.
    |   health:   '/up'               -- A simple health-check endpoint that
    |                                    returns 200 OK, useful for load balancers
    |                                    and uptime monitors.
    */
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    /*
    |----------------------------------------------------------------------
    | Middleware Aliases
    |----------------------------------------------------------------------
    | Registers short alias names for custom middleware so they can be
    | referenced concisely in route definitions (e.g., ->middleware('role:admin,manager')).
    |
    |   'role'     => CheckRole          -- Verifies the authenticated user has
    |                                       one of the comma-separated roles
    |                                       passed as parameters. Used to restrict
    |                                       write operations to admins and managers.
    |
    |   'location' => EnsureLocationAccess -- Reads the X-Location-Id request
    |                                       header, validates the user belongs to
    |                                       that location (or is an admin), and
    |                                       injects the resolved location_id into
    |                                       the request for downstream controllers.
    */
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'location' => \App\Http\Middleware\EnsureLocationAccess::class,
        ]);
    })
    /*
    |----------------------------------------------------------------------
    | Exception Handling
    |----------------------------------------------------------------------
    | Placeholder for custom exception rendering and reporting logic.
    | Currently uses Laravel's default exception handler. Custom behavior
    | (e.g., sending specific error formats for API consumers) can be
    | added inside this callback.
    */
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
