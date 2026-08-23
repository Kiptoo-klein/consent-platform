<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizationUserInvitationController extends Controller
{
    public function create(
        Request $request,
        string $token
    ): View|RedirectResponse {
        $email = strtolower(
            trim(
                (string) $request->query('email')
            )
        );

        $user = User::query()
            ->where('email', $email)
            ->whereNotNull('organization_id')
            ->first();

        if ($user === null) {
            abort(404);
        }

        if (! $user->is_active) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' =>
                        'This invitation is no longer active. '
                        .'Contact your organization administrator.',
                ]);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()
                ->route('login')
                ->with(
                    'status',
                    'Your account setup is already complete. '
                    .'You can sign in.'
                );
        }

        $broker = Password::broker(
            'organization_invitations'
        );

        if (! $broker->tokenExists($user, $token)) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' =>
                        'This invitation is invalid or has expired. '
                        .'Ask your organization administrator '
                        .'for a new invitation.',
                ]);
        }

        $user->load('roles', 'organization');

        return view(
            'auth.complete-organization-invitation',
            [
                'user' => $user,
                'token' => $token,
                'roleName' =>
                    $user->roles->first()?->name
                    ?? 'Organization User',
            ]
        );
    }

    public function store(
        Request $request,
        string $token
    ): RedirectResponse {
        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $user = User::query()
            ->where(
                'email',
                $validated['email']
            )
            ->whereNotNull('organization_id')
            ->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' =>
                    'This invitation could not be found.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' =>
                    'This invitation is no longer active. '
                    .'Contact your organization administrator.',
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()
                ->route('login')
                ->with(
                    'status',
                    'Your account setup is already complete. '
                    .'You can sign in.'
                );
        }

        $broker = Password::broker(
            'organization_invitations'
        );

        if (! $broker->tokenExists($user, $token)) {
            throw ValidationException::withMessages([
                'email' =>
                    'This invitation is invalid or has expired. '
                    .'Ask your organization administrator '
                    .'for a new invitation.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make(
                $validated['password']
            ),
        ])->save();

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $broker->deleteToken($user);

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Your account setup is complete. '
                .'You can now sign in.'
            );
    }
}
