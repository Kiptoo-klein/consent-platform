<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent disabled users and users of archived organizations from
 * accessing authenticated areas.
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

        if ($user === null) {
            return $next($request);
        }

        /*
         * Refresh account state so changes made by another administrator are
         * enforced on the user's very next authenticated request.
         */
        $user->refresh();

        if (! $user->is_active) {
            return $this->logoutWithError(
                $request,
                'Your account has been disabled. '
                .'Contact an administrator for assistance.'
            );
        }

        /*
         * Organization archival is an organization-wide access lock.
         * Platform users have no organization_id and are unaffected.
         */
        if ($user->organization_id !== null) {
            $user->load('organization');

            if ($user->organization?->isArchived()) {
                return $this->logoutWithError(
                    $request,
                    'This organization has been archived. '
                    .'You can request organization restoration.',
                    [
                        'type' =>
                            'organization',

                        'user_id' =>
                            $user->id,

                        'expires_at' =>
                            now()
                                ->addMinutes(10)
                                ->timestamp,
                    ]
                );
            }
        }

        return $next($request);
    }

    /**
     * @param array{
     *     type: string,
     *     user_id: int,
     *     expires_at: int
     * }|null $restorationContext
     */
    private function logoutWithError(
        Request $request,
        string $message,
        ?array $restorationContext = null
    ): RedirectResponse {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($restorationContext !== null) {
            $request->session()->put(
                'account_restoration',
                $restorationContext
            );
        }

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => $message,
            ]);
    }
}
