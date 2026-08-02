<?php

namespace App\Http\Controllers;

use App\Models\ConsentNotification;
use App\Models\ConsentSession;
use App\Services\ConsentExpiryService;
use App\Services\ConsentNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConsentNotificationController extends Controller
{
    public function send(
        Request $request,
        ConsentSession $consentSession,
        ConsentExpiryService $consentExpiryService,
        ConsentNotificationService $notificationService
    ): RedirectResponse {
        $this->ensureSessionBelongsToOrganization(
            $consentSession
        );

        $consentExpiryService->expireIfDue(
            consentSession: $consentSession,
            source: 'manual_email_attempt',
            request: $request
        );

        $consentSession->refresh();

        if ($error = $notificationService->eligibilityError(
            $consentSession
        )) {
            return back()->withErrors([
                'email' => $error,
            ]);
        }

        if ($notificationService->wasRecentlySent(
            $consentSession
        )) {
            return back()->withErrors([
                'email' =>
                    'An email was sent recently. Please wait a few minutes before sending another.',
            ]);
        }

        $notification = $notificationService->sendInitial(
            consentSession: $consentSession,
            actorUserId: Auth::id(),
            trigger: ConsentNotification::TRIGGER_MANUAL
        );

        if ($notification->isFailed()) {
            return back()->withErrors([
                'email' =>
                    'The signing email could not be delivered. Check the mail configuration and try again.',
            ]);
        }

        $message = $notification->isQueued()
            ? "Signing email queued for {$notification->recipient_email}."
            : "Signing email sent to {$notification->recipient_email}.";

        return back()->with(
            'email_success',
            $message
        );
    }

    public function remind(
        Request $request,
        ConsentSession $consentSession,
        ConsentExpiryService $consentExpiryService,
        ConsentNotificationService $notificationService
    ): RedirectResponse {
        $this->ensureSessionBelongsToOrganization(
            $consentSession
        );

        $consentExpiryService->expireIfDue(
            consentSession: $consentSession,
            source: 'manual_reminder_attempt',
            request: $request
        );

        $consentSession->refresh();

        if ($error = $notificationService->eligibilityError(
            $consentSession
        )) {
            return back()->withErrors([
                'email' => $error,
            ]);
        }

        if ($notificationService->wasRecentlySent(
            $consentSession
        )) {
            return back()->withErrors([
                'email' =>
                    'An email was sent recently. Please wait a few minutes before sending another.',
            ]);
        }

        $notification =
            $notificationService->sendManualReminder(
                consentSession: $consentSession,
                actorUserId: Auth::id()
            );

        if ($notification->isFailed()) {
            return back()->withErrors([
                'email' =>
                    'The reminder could not be delivered. Check the mail configuration and try again.',
            ]);
        }

        return back()->with(
            'email_success',
            "Reminder sent to {$notification->recipient_email}."
        );
    }

    private function ensureSessionBelongsToOrganization(
        ConsentSession $consentSession
    ): void {
        abort_unless(
            (int) $consentSession->organization_id
                === (int) Auth::user()->organization_id,
            403
        );
    }
}
