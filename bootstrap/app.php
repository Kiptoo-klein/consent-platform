<?php

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

        // SECURITY_HARDENING_WEB_MIDDLEWARE
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHardeningMiddleware::class,
        ]);

        $middleware->alias([
            'platform.role' => EnsurePlatformRole::class,
            'active.user' => EnsureUserIsActive::class,
            'organization.user' => EnsureOrganizationUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*')
        );
    })
    ->create();
