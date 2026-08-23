<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Handles authentication for both platform and organization users.
 *
 * After a successful login, users are redirected according to the type of
 * account they have:
 *
 * - Platform users are sent to the platform administration dashboard.
 * - Organization users are sent to the organization dashboard.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /*
        |--------------------------------------------------------------------------
        | Platform user redirect
        |--------------------------------------------------------------------------
        |
        | Platform users are not attached to an organization and therefore
        | are not affected by organization archival.
        |
        */
        if ($request->user()->platform_role_id !== null) {
            return redirect()->route('platform.dashboard');
        }

        /*
        |--------------------------------------------------------------------------
        | Organization user redirect
        |--------------------------------------------------------------------------
        |
        | An archived organization is an organization-wide access lock.
        | Reject the login even when the individual user account is active.
        |
        */
        if ($request->user()->organization_id !== null) {
            $request->user()->load('organization');

            if ($request->user()->organization?->isArchived()) {
                $verifiedUserId =
                    $request->user()->id;

                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $request->session()->put(
                    'account_restoration',
                    [
                        'type' =>
                            'organization',

                        'user_id' =>
                            $verifiedUserId,

                        'expires_at' =>
                            now()
                                ->addMinutes(10)
                                ->timestamp,
                    ]
                );

                return redirect()
                    ->route('login')
                    ->withErrors([
                        'email' =>
                            'This organization has been archived. '
                            .'You can request organization restoration.',
                    ]);
            }

            return redirect()->route('dashboard');
        }

        /*
        |--------------------------------------------------------------------------
        | Invalid account state
        |--------------------------------------------------------------------------
        */
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors([
                'email' =>
                    'This account is not assigned to the platform '
                    .'or an organization.',
            ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
