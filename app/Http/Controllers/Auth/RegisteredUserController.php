<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewOrganizationNotificationJob;
use App\Models\User;
use App\Services\OrganizationRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming organization registration request.
     *
     * @throws ValidationException
     */
    public function store(
        Request $request,
        OrganizationRegistrationService $registration
    ): RedirectResponse
    {
        $validated = $request->validate([
            'organization_name' => [
                'required',
                'string',
                'max:255',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
            ],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $user = $registration->registerFounder(
            organizationName:
                $validated['organization_name'],

            name:
                $validated['name'],

            email:
                $validated['email'],

            password:
                $validated['password']
        );

        try {
            event(new Registered($user));
        } catch (\Throwable $exception) {
            /*
             * The account and organization have already committed.
             * A temporary verification-email dispatch failure must not
             * destroy the registration. The user can resend the link.
             */
            report($exception);
        }

        /*
         * Notify the platform Super Admin that a new organization
         * has been successfully created.
         *
         * This is intentionally dispatched after the transaction
         * has completed successfully.
         */
        SendNewOrganizationNotificationJob::dispatch(
            $user->organization_id
        );

        Auth::login($user);

        return redirect()
            ->route('verification.notice')
            ->with(
                'status',
                'verification-link-sent'
            );
    }
}
