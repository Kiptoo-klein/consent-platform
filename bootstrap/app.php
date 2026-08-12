<?php

use App\Http\Middleware\EnsureOrganizationSubscriptionAccess;
use App\Http\Middleware\EnsureOrganizationUser;
use App\Http\Middleware\EnsurePlatformRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        /*
         * Laravel Cloud terminates requests behind its managed proxy.
         * Trust forwarded host/protocol information so absolute URLs
         * use the visitor-facing custom domain instead of the internal
         * laravel.cloud deployment hostname.
         */
        $middleware->trustProxies(at: '*');

        // SECURITY_HARDENING_WEB_MIDDLEWARE
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHardeningMiddleware::class,
        ]);

        $middleware->alias([
            'platform.role' => EnsurePlatformRole::class,
            'active.user' => EnsureUserIsActive::class,
            'organization.user' => EnsureOrganizationUser::class,
            'organization.role' =>
                \App\Http\Middleware\EnsureOrganizationRole::class,
            'organization.subscription' =>
                EnsureOrganizationSubscriptionAccess::class,

            'kiosk.device' =>
                \App\Http\Middleware\EnsureSigningStationDeviceLease::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*')
        );
    })
    ->create();
