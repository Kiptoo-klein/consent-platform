<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent disabled users from accessing authenticated areas.
 *
 * Any authenticated user whose is_active value is false will be logged out,
 * their session invalidated, and their CSRF token regenerated.
 */
class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response|RedirectResponse {
        $user = $request->user();

        /*
         * Allow unauthenticated requests to continue.
         *
         * The normal "auth" middleware is responsible for redirecting guests
         * away from protected routes.
         */
        if ($user === null) {
            return $next($request);
        }

        /*
         * Refresh the user so a newly disabled account is detected even when
         * the authenticated user instance was loaded earlier in the request.
         */
        $user->refresh();

        if (! $user->is_active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' =>
                        'Your account has been disabled. '
                        .'Contact an administrator for assistance.',
                ]);
        }

        return $next($request);
    }
}
