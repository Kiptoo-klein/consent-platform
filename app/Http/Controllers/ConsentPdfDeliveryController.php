<?php

namespace App\Http\Controllers;

use App\Jobs\RetrySignedConsentPdfJob;
use App\Models\ConsentPdfDelivery;
use App\Models\ConsentSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ConsentPdfDeliveryController extends Controller
{
    /**
     * Queue a fresh signed-PDF copy for a completed public-station record.
     */
    public function retry(
        ConsentSession $consentSession
    ): RedirectResponse {
        abort_unless(
            (int) $consentSession->organization_id
                === (int) Auth::user()->organization_id,
            403
        );

        if (! Schema::hasTable('consent_pdf_deliveries')) {
            return back()->withErrors([
                'signed_pdf_delivery' =>
                    'Signed PDF delivery is not ready. Run php artisan migrate first.',
            ]);
        }

        if (blank($consentSession->signing_station_id)) {
            return back()->withErrors([
                'signed_pdf_delivery' =>
                    'Automatic signed-PDF delivery only applies to public signing-station records.',
            ]);
        }

        if (! $consentSession->isCompleted()) {
            return back()->withErrors([
                'signed_pdf_delivery' =>
                    'The consent must be completed before its signed PDF can be emailed.',
            ]);
        }

        $recipient = trim((string) $consentSession->signer_email);

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors([
                'signed_pdf_delivery' =>
                    'This signer does not have a valid email address.',
            ]);
        }

        if (
            blank($consentSession->pdf_path)
            || blank($consentSession->pdf_generated_at)
        ) {
            return back()->withErrors([
                'signed_pdf_delivery' =>
                    'The signed PDF is still being generated. Refresh the record before retrying delivery.',
            ]);
        }

        $delivery = ConsentPdfDelivery::query()->firstOrNew([
            'consent_session_id' => $consentSession->id,
        ]);

        if (
            $delivery->exists
            && $delivery->status === ConsentPdfDelivery::STATUS_PROCESSING
            && $delivery->processing_at
            && $delivery->processing_at->gt(
                now()->subMinutes(
                    max(
                        1,
                        (int) config(
                            'signed-consent-delivery.processing_stale_minutes',
                            10
                        )
                    )
                )
            )
        ) {
            return back()->withErrors([
                'signed_pdf_delivery' =>
                    'A delivery attempt is already being processed.',
            ]);
        }

        $delivery->fill([
            'recipient' => $recipient,
            'status' => ConsentPdfDelivery::STATUS_PENDING,
            'processing_at' => null,
            'failed_at' => null,
            'last_error' => null,
        ])->save();

        RetrySignedConsentPdfJob::dispatch(
            (int) $consentSession->id
        )->afterCommit();

        return back()->with(
            'success',
            $delivery->sent_at
                ? 'Another signed PDF copy was queued for delivery.'
                : 'Signed PDF delivery was queued for retry.'
        );
    }
}
