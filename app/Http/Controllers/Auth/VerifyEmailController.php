<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\OrganizationWelcome;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            $user = $request->user();

            event(new Verified($user));

            /*
             * Only the original organization founder / Billing Owner
             * receives the Free Evaluation workspace welcome message.
             */
            if (
                $user->organization_id !== null
                && $user->billingSubscription()->exists()
            ) {
                $organization = $user->organization;

                if ($organization !== null) {
                    try {
                        $user->notify(
                            new OrganizationWelcome(
                                $organization->name
                            )
                        );
                    } catch (\Throwable $exception) {
                        /*
                         * Verification remains successful even if
                         * welcome-email dispatch temporarily fails.
                         */
                        report($exception);
                    }
                }
            }
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
