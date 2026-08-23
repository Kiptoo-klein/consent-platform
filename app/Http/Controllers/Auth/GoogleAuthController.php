<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\OrganizationWelcome;
use App\Services\OrganizationRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private const SESSION_INTENT =
        'google_auth.intent';

    private const SESSION_ORGANIZATION_NAME =
        'google_auth.organization_name';

    private const INTENT_REGISTER =
        'register';

    private const INTENT_LOGIN =
        'login';

    /**
     * Begin Google registration after collecting the organization name.
     */
    public function redirectFromRegistration(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'organization_name' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $request->session()->put(
            self::SESSION_INTENT,
            self::INTENT_REGISTER
        );

        $request->session()->put(
            self::SESSION_ORGANIZATION_NAME,
            trim($validated['organization_name'])
        );

        return Socialite::driver('google')
            ->redirect();
    }

    /**
     * Begin Google authentication from the normal login screen.
     */
    public function redirectFromLogin(
        Request $request
    ): RedirectResponse {
        $request->session()->put(
            self::SESSION_INTENT,
            self::INTENT_LOGIN
        );

        $request->session()->forget(
            self::SESSION_ORGANIZATION_NAME
        );

        return Socialite::driver('google')
            ->redirect();
    }

    /**
     * Handle the common Google OAuth callback.
     */
    public function callback(
        Request $request,
        OrganizationRegistrationService $registration
    ): RedirectResponse {
        $intent = $request->session()->get(
            self::SESSION_INTENT
        );

        if (
            ! in_array(
                $intent,
                [
                    self::INTENT_REGISTER,
                    self::INTENT_LOGIN,
                ],
                true
            )
        ) {
            $this->clearGoogleSession($request);

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' =>
                        'Your Google sign-in session expired. '
                        .'Please try again.',
                ]);
        }

        try {
            $googleUser =
                Socialite::driver('google')
                    ->user();
        } catch (\Throwable $exception) {
            report($exception);

            $destination =
                $intent === self::INTENT_REGISTER
                    ? 'register'
                    : 'login';

            $this->clearGoogleSession($request);

            return redirect()
                ->route($destination)
                ->withErrors([
                    'email' =>
                        'Google authentication could not be completed. '
                        .'Please try again.',
                ]);
        }

        $identity =
            $this->googleIdentity(
                $googleUser
            );

        if ($identity === null) {
            $destination =
                $intent === self::INTENT_REGISTER
                    ? 'register'
                    : 'login';

            $this->clearGoogleSession($request);

            return redirect()
                ->route($destination)
                ->withErrors([
                    'email' =>
                        'Google did not provide a verified email '
                        .'address for this account.',
                ]);
        }

        if ($intent === self::INTENT_REGISTER) {
            return $this->completeRegistration(
                request: $request,
                registration: $registration,
                identity: $identity
            );
        }

        return $this->completeLogin(
            request: $request,
            identity: $identity
        );
    }

    /**
     * @param array{
     *     id: string,
     *     name: string,
     *     email: string
     * } $identity
     */
    private function completeRegistration(
        Request $request,
        OrganizationRegistrationService $registration,
        array $identity
    ): RedirectResponse {
        $organizationName = trim(
            (string) $request
                ->session()
                ->get(
                    self::SESSION_ORGANIZATION_NAME,
                    ''
                )
        );

        if (
            $organizationName === ''
            || Str::length($organizationName) > 255
        ) {
            $this->clearGoogleSession($request);

            return redirect()
                ->route('register')
                ->withErrors([
                    'organization_name' =>
                        'Enter your organization name before '
                        .'continuing with Google.',
                ]);
        }

        /*
         * Registration never converts an existing account into a new
         * organization. Existing users must authenticate through Login.
         */
        $existingUser =
            User::withTrashed()
                ->where(
                    'google_id',
                    $identity['id']
                )
                ->orWhereRaw(
                    'LOWER(email) = ?',
                    [$identity['email']]
                )
                ->first();

        if ($existingUser !== null) {
            $this->clearGoogleSession($request);

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' =>
                        'An eConsent account already exists for '
                        .'this Google account. Sign in instead.',
                ]);
        }

        /*
         * Google has already proven ownership of this email, so the
         * founder starts verified. The random password satisfies the
         * database contract but is never shown, emailed or stored
         * outside the password hash.
         *
         * registerFounder() also sets this email as organizations.email,
         * preserving the default organization-email rule.
         */
        $user = $registration->registerFounder(
            organizationName:
                $organizationName,

            name:
                $identity['name'],

            email:
                $identity['email'],

            password:
                Str::random(64),

            emailVerifiedAt:
                now(),

            googleId:
                $identity['id']
        );

        /*
         * Preserve the normal Laravel registration event contract.
         * Because this user is already verified, Laravel must not send
         * another verification message.
         */
        try {
            event(
                new Registered($user)
            );
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            $user->notify(
                new OrganizationWelcome(
                    $organizationName
                )
            );
        } catch (\Throwable $exception) {
            /*
             * Welcome delivery must never undo a completed registration.
             */
            report($exception);
        }

        $this->clearGoogleSession($request);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('dashboard');
    }

    /**
     * @param array{
     *     id: string,
     *     name: string,
     *     email: string
     * } $identity
     */
    private function completeLogin(
        Request $request,
        array $identity
    ): RedirectResponse {
        /*
         * A stable Google subject ID is the strongest existing link.
         */
        $user =
            User::withTrashed()
                ->where(
                    'google_id',
                    $identity['id']
                )
                ->first();

        if ($user === null) {
            /*
             * Safe account linking is allowed only when the local
             * account already has a verified email matching Google's
             * verified email.
             */
            $user =
                User::withTrashed()
                    ->whereRaw(
                        'LOWER(email) = ?',
                        [$identity['email']]
                    )
                    ->first();

            if ($user === null) {
                $this->clearGoogleSession($request);

                return redirect()
                    ->route('register')
                    ->withErrors([
                        'email' =>
                            'No eConsent account exists for this '
                            .'Google email. Enter your organization '
                            .'name to create one.',
                    ])
                    ->withInput([
                        'name' =>
                            $identity['name'],

                        'email' =>
                            $identity['email'],
                    ]);
            }

            if (
                $user->google_id !== null
                && $user->google_id !== $identity['id']
            ) {
                return $this->rejectLogin(
                    request: $request,
                    message:
                        'This account is already linked to a '
                        .'different Google identity.'
                );
            }

            if (
                $user->trashed()
                || ! $user->is_active
            ) {
                return $this->rejectLogin(
                    request: $request,
                    message:
                        'This account is not currently active. '
                        .'Contact an administrator for assistance.'
                );
            }

            /*
             * Do not let Google Login bypass founder verification or
             * an invited user's account-setup flow.
             */
            if (! $user->hasVerifiedEmail()) {
                return $this->rejectLogin(
                    request: $request,
                    message:
                        'This account has not completed setup yet. '
                        .'Use your verification or invitation email first.'
                );
            }

            $user->forceFill([
                'google_id' =>
                    $identity['id'],
            ])->save();
        } else {
            if (
                $user->trashed()
                || ! $user->is_active
            ) {
                return $this->rejectLogin(
                    request: $request,
                    message:
                        'This account is not currently active. '
                        .'Contact an administrator for assistance.'
                );
            }

            if (! $user->hasVerifiedEmail()) {
                return $this->rejectLogin(
                    request: $request,
                    message:
                        'This account has not completed setup yet. '
                        .'Use your verification or invitation email first.'
                );
            }
        }

        $this->clearGoogleSession($request);

        Auth::login($user);

        $request->session()->regenerate();

        if ($user->platform_role_id !== null) {
            return redirect()
                ->route('platform.dashboard');
        }

        if ($user->organization_id !== null) {
            return redirect()
                ->route('dashboard');
        }

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

    private function rejectLogin(
        Request $request,
        string $message
    ): RedirectResponse {
        $this->clearGoogleSession($request);

        Auth::guard('web')->logout();

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => $message,
            ]);
    }

    /**
     * Return only the identity data the application actually needs.
     *
     * No OAuth access token, refresh token, avatar or provider profile
     * payload is persisted.
     *
     * @return array{
     *     id: string,
     *     name: string,
     *     email: string
     * }|null
     */
    private function googleIdentity(
        SocialiteUser $googleUser
    ): ?array {
        $raw =
            $googleUser instanceof AbstractUser
                ? $googleUser->getRaw()
                : [];

        $verifiedValue =
            $raw['email_verified']
            ?? $raw['verified_email']
            ?? false;

        $verified = filter_var(
            $verifiedValue,
            FILTER_VALIDATE_BOOLEAN
        );

        $id = trim(
            (string) $googleUser->getId()
        );

        $email = Str::lower(
            trim(
                (string) $googleUser->getEmail()
            )
        );

        if (
            ! $verified
            || $id === ''
            || Str::length($id) > 255
            || $email === ''
            || Str::length($email) > 255
            || filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            return null;
        }

        $name = trim(
            (string) $googleUser->getName()
        );

        if ($name === '') {
            $name = Str::before(
                $email,
                '@'
            );
        }

        $name = Str::limit(
            $name,
            255,
            ''
        );

        return [
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ];
    }

    private function clearGoogleSession(
        Request $request
    ): void {
        $request->session()->forget([
            self::SESSION_INTENT,
            self::SESSION_ORGANIZATION_NAME,
        ]);
    }
}
