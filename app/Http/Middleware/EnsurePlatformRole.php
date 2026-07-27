<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $role
    ): Response {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser) {
            abort(403, 'You must be logged in.');
        }

        $user = $authenticatedUser->fresh(['platformRole']);

        if (! $user || ! $user->platformRole) {
            abort(403, 'No platform role is assigned to this account.');
        }

        if ($user->platformRole->slug !== $role) {
            abort(403, 'Your platform role does not allow access.');
        }

        return $next($request);
    }
}
