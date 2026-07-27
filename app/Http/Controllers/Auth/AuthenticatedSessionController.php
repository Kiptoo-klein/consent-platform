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
        | A user with a platform role belongs to the central administration
        | area. Platform users do not need an organization_id.
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
        | A normal organization user must belong to an organization before
        | they can access the organization workspace.
        |
        */
        if ($request->user()->organization_id !== null) {
            return redirect()->route('dashboard');
        }

        /*
        |--------------------------------------------------------------------------
        | Invalid account state
        |--------------------------------------------------------------------------
        |
        | This account has neither a platform role nor an organization. Log it
        | out immediately so it cannot remain in an unusable authenticated
        | state.
        |
        */
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => 'This account is not assigned to the platform or an organization.',
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
