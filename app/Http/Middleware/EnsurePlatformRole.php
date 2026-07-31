<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformRole
{
    /**
     * Restrict a route to one or more platform roles.
     *
     * Examples:
     *
     * platform.role:super-admin
     * platform.role:super-admin,billing
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $authenticatedUser =
            $request->user();

        if (! $authenticatedUser) {
            abort(
                403,
                'You must be logged in.'
            );
        }

        $user =
            $authenticatedUser->fresh([
                'platformRole',
            ]);

        if (
            ! $user
            || ! $user->platformRole
        ) {
            abort(
                403,
                'No platform role is assigned to this account.'
            );
        }

        if (
            $roles === []
            || ! in_array(
                $user->platformRole->slug,
                $roles,
                true
            )
        ) {
            abort(
                403,
                'Your platform role does not allow access.'
            );
        }

        return $next($request);
    }
}
