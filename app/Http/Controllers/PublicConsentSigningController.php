<?php

namespace App\Http\Controllers;


use App\Services\SigningStationFlowTracker;
use App\Jobs\GenerateConsentPdfJob;
use App\Models\ConsentSession;
use App\Models\ConsentSignature;
use App\Services\ConsentAuditService;
use App\Services\ConsentExpiryService;
use App\Services\DynamicFormFieldService;
use App\Services\SubscriptionUsageLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicConsentSigningController extends Controller
{
    /**
     * Display the public consent review page.
     */
    public function show(
        Request $request,
        string $accessToken,
        ConsentExpiryService $consentExpiryService,
        DynamicFormFieldService $dynamicFormFieldService
    ): View {
        $consentSession = $this->findSession(
            accessToken: $accessToken,
            consentExpiryService: $consentExpiryService,
            request: $request,
            source: 'public_review_page'
        );

        $consentSession->load([
            'organization',
            'consentTemplate',
            'consentTemplateVersion',
            'signingStation',
            'signature',
        ]);

        if (
            ! $consentSession->isCompleted()
            && app(
                SubscriptionUsageLimitService::class
            )
                ->signedConsentCapacity(
                    (int) $consentSession
                        ->organization_id
                )['reached']
        ) {
            return view(
                'public-consent.signed-consent-limit-reached',
                compact('consentSession')
            );
        }

        return view('public-consent.show', [
            'consentSession' => $consentSession,
            'publishedVersion' => $consentSession->consentTemplateVersion,
            'additionalFields' =>
                $dynamicFormFieldService->fieldsFromSchema(
                    $consentSession
                        ->consentTemplateVersion
                        ?->template_schema
                ),
        ]);
    }

    /**
     * Save consent responses and move the record into progress.
     */
    public function update(
        Request $request,
        string $accessToken,
        ConsentExpiryService $consentExpiryService,
        DynamicFormFieldService $dynamicFormFieldService
    ): RedirectResponse {
        $consentSession = $this->findSession(
            accessToken: $accessToken,
            consentExpiryService: $consentExpiryService,
            request: $request,
            source: 'public_response_submission'
        );

        if ($consentSession->isCompleted()) {
            return redirect()
                ->route(
                    'public-consent.completed',
                    $consentSession->access_token
                );
        }

        if ($consentSession->isCancelled()) {
            return redirect()
                ->route(
                    'public-consent.show',
                    $consentSession->access_token
                )
                ->withErrors([
                    'record' =>
                        'This consent record has been cancelled.',
                ]);
        }

        if ($consentSession->isExpired()) {
            return redirect()
                ->route(
                    'public-consent.show',
                    $consentSession->access_token
                )
                ->withErrors([
                    'record' =>
                        'The signing deadline for this consent record has passed.',
                ]);
        }

        if (
            app(
                SubscriptionUsageLimitService::class
            )
                ->signedConsentCapacity(
                    (int) $consentSession
                        ->organization_id
                )['reached']
        ) {
            return redirect()->route(
                'public-consent.show',
                $consentSession
                    ->access_token
            );
        }

        $additionalFields =
            $dynamicFormFieldService->fieldsFromSchema(
                $consentSession
                    ->consentTemplateVersion
                    ?->template_schema
            );

        $validationRules =
            $dynamicFormFieldService->validationRules(
                $additionalFields
            );

        $validated = $request->validate($validationRules);

        $responsesSaved = DB::transaction(
            function () use (
                $consentSession,
                $validated,
                $request,
                $consentExpiryService
            ): bool {
                $lockedSession = ConsentSession::query()
                    ->whereKey($consentSession->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $consentExpiryService->expireLockedIfDue(
                        consentSession: $lockedSession,
                        source: 'public_response_submission',
                        request: $request
                    )
                ) {
                    return false;
                }

                if ($lockedSession->isCompleted()) {
                    throw ValidationException::withMessages([
                        'record' =>
                            'This consent record has already been completed.',
                    ]);
                }

                if ($lockedSession->isCancelled()) {
                    throw ValidationException::withMessages([
                        'record' =>
                            'This consent record has been cancelled.',
                    ]);
                }

                if ($lockedSession->isExpired()) {
                    return false;
                }

                $lockedSession->update([
                    'responses' =>
                        $validated['responses'] ?? [],

                    'status' =>
                        ConsentSession::STATUS_IN_PROGRESS,

                    'started_at' =>
                        $lockedSession->started_at ?? now(),
                ]);

                return true;
            },
            3
        );

        if (! $responsesSaved) {
            return redirect()
                ->route(
                    'public-consent.show',
                    $consentSession->access_token
                )
                ->withErrors([
                    'record' =>
                        'The signing deadline for this consent record has passed.',
                ]);
        }

        return redirect()
            ->route(
                'public-consent.signature',
                $consentSession->access_token
            );
    }

    /**
     * Display the signature page.
     */
    public function signature(
        Request $request,
        string $accessToken,
        ConsentExpiryService $consentExpiryService
    ): View|RedirectResponse {
        $consentSession = $this->findSession(
            accessToken: $accessToken,
            consentExpiryService: $consentExpiryService,
            request: $request,
            source: 'public_signature_page'
        );

        if ($consentSession->isCompleted()) {
            return redirect()
                ->route(
                    'public-consent.completed',
                    $consentSession->access_token
                );
        }

        if (
            app(
                SubscriptionUsageLimitService::class
            )
                ->signedConsentCapacity(
                    (int) $consentSession
                        ->organization_id
                )['reached']
        ) {
            $consentSession->load(
                'organization'
            );

            return view(
                'public-consent.signed-consent-limit-reached',
                compact('consentSession')
            );
        }

        $consentSession->load([
            'organization',
            'consentTemplate',
            'consentTemplateVersion',
            'signature',
        ]);

        return view('public-consent.signature', [
            'consentSession' => $consentSession,
        ]);
    }

    /**
     * Save the signature and complete the consent record.
     */
    public function complete(
        Request $request,
        string $accessToken,
        ConsentAuditService $consentAuditService,
        ConsentExpiryService $consentExpiryService,
        SubscriptionUsageLimitService $usageLimitService
    ): RedirectResponse {
        $consentSession = $this->findSession(
            accessToken: $accessToken,
            consentExpiryService: $consentExpiryService,
            request: $request,
            source: 'public_signature_submission'
        );

        if ($consentSession->isCompleted()) {
            return redirect()
                ->route(
                    'public-consent.completed',
                    $consentSession->access_token
                );
        }

        if ($consentSession->isCancelled()) {
            return redirect()
                ->route(
                    'public-consent.show',
                    $consentSession->access_token
                )
                ->withErrors([
                    'record' =>
                        'This consent record has been cancelled.',
                ]);
        }

        if ($consentSession->isExpired()) {
            return redirect()
                ->route(
                    'public-consent.show',
                    $consentSession->access_token
                )
                ->withErrors([
                    'record' =>
                        'The signing deadline for this consent record has passed.',
                ]);
        }

        $validated = $request->validate([
            'signature_data' => [
                'required',
                'string',
                'max:2500000',
            ],

            'confirmation' => [
                'accepted',
            ],
        ], [
            'signature_data.required' =>
                'Please draw your signature before submitting.',

            'signature_data.max' =>
                'The signature image is too large. Please clear it and sign again.',

            'confirmation.accepted' =>
                'You must confirm that the signature is yours.',
        ]);

        $signatureData = $validated['signature_data'];

        if (! str_starts_with(
            $signatureData,
            'data:image/png;base64,'
        )) {
            throw ValidationException::withMessages([
                'signature_data' =>
                    'The signature image format is invalid.',
            ]);
        }

        $encodedImage = substr(
            $signatureData,
            strlen('data:image/png;base64,')
        );

        $decodedImage = base64_decode(
            $encodedImage,
            true
        );

        if ($decodedImage === false) {
            throw ValidationException::withMessages([
                'signature_data' =>
                    'The signature image could not be processed.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Signature size protection
        |--------------------------------------------------------------------------
        */
        if (strlen($decodedImage) > 1500000) {
            throw ValidationException::withMessages([
                'signature_data' =>
                    'The signature image is too large. Please clear it and sign again.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | PNG validation
        |--------------------------------------------------------------------------
        */
        $pngSignature = "\x89PNG\r\n\x1a\n";

        if (! str_starts_with($decodedImage, $pngSignature)) {
            throw ValidationException::withMessages([
                'signature_data' =>
                    'The submitted signature is not a valid PNG image.',
            ]);
        }

        $completed = DB::transaction(function () use (
            $consentSession,
            $signatureData,
            $request,
            $consentAuditService,
            $consentExpiryService,
            $usageLimitService
        ): bool {
            $lockedSession = ConsentSession::query()
                ->whereKey($consentSession->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $consentExpiryService->expireLockedIfDue(
                    consentSession: $lockedSession,
                    source: 'public_signature_submission',
                    request: $request
                )
            ) {
                return false;
            }

            if ($lockedSession->isCompleted()) {
                throw ValidationException::withMessages([
                    'record' =>
                        'This consent record has already been completed.',
                ]);
            }

            if ($lockedSession->isCancelled()) {
                throw ValidationException::withMessages([
                    'record' =>
                        'This consent record has been cancelled.',
                ]);
            }

            if ($lockedSession->isExpired()) {
                return false;
            }

            try {
                $usageLimitService
                    ->assertSignedConsentSlotAvailableLocked(
                        (int) $lockedSession
                            ->organization_id,
                        (int) $lockedSession->id
                    );
            } catch (ValidationException $exception) {
                if (
                    array_key_exists(
                        'subscription',
                        $exception->errors()
                    )
                ) {
                    throw ValidationException::withMessages([
                        'record' =>
                            'Signing is temporarily unavailable. '
                            .'Please contact the organization or '
                            .'try again later.',
                    ]);
                }

                throw $exception;
            }

            $signedAt = now();
            $completedAt = now();

            ConsentSignature::query()->updateOrCreate(
                [
                    'consent_session_id' =>
                        $lockedSession->id,
                ],
                [
                    'signer_name' =>
                        $lockedSession->signer_name,

                    'signature_data' =>
                        $signatureData,

                    'signature_type' =>
                        ConsentSignature::TYPE_DRAWN,

                    'signed_at' =>
                        $signedAt,

                    'ip_address' =>
                        $request->ip(),

                    'user_agent' =>
                        $request->userAgent(),
                ]
            );

            $lockedSession->update([
                'status' =>
                    ConsentSession::STATUS_COMPLETED,

                'started_at' =>
                    $lockedSession->started_at ?? $completedAt,

                'completed_at' =>
                    $completedAt,

                'cancelled_at' =>
                    null,
            ]);

            $consentAuditService->record(
                consentSession: $lockedSession,
                eventType: 'consent.completed',
                description:
                    'The signer completed the consent record.',
                metadata: [
                    'signer_name' =>
                        $lockedSession->signer_name,

                    'signature_type' =>
                        ConsentSignature::TYPE_DRAWN,

                    'signed_at' =>
                        $signedAt->toIso8601String(),

                    'completed_at' =>
                        $completedAt->toIso8601String(),
                ],
                request: $request
            );

            return true;
        }, 3);

        if (! $completed) {
            return redirect()
                ->route(
                    'public-consent.show',
                    $consentSession->access_token
                )
                ->withErrors([
                    'record' =>
                        'The signing deadline for this consent record has passed.',
                ]);
        }

        GenerateConsentPdfJob::dispatch(
            $consentSession->id
        )->afterCommit();

        // KIOSK_ANALYTICS_CONSENT_COMPLETED
        app(SigningStationFlowTracker::class)
            ->completeConsent($request, $consentSession);

        return redirect()
            ->route(
                'public-consent.completed',
                $consentSession->access_token
            );
    }

    /**
     * Cancel an incomplete consent record and reset the signing station.
     */
    public function cancel(
        Request $request,
        string $accessToken,
        ConsentAuditService $consentAuditService,
        ConsentExpiryService $consentExpiryService
    ): RedirectResponse {
        $consentSession = $this->findSession(
            accessToken: $accessToken,
            consentExpiryService: $consentExpiryService,
            request: $request,
            source: 'public_cancel_attempt'
        );

        // KIOSK_ANALYTICS_CONSENT_CANCELLED
        app(SigningStationFlowTracker::class)->cancelConsent(
            $request,
            $consentSession,
            $request->boolean('timed_out')
        );

        $consentSession->loadMissing('signingStation');

        if ($consentSession->isExpired()) {
            if ($consentSession->signingStation) {
                $request->session()->forget(
                    'signing_station_reviewed_'
                    .$consentSession->signingStation->id
                );

                return redirect()->route(
                    $this->stationReturnRoute($consentSession),
                    [
                        'stationToken' =>
                            $consentSession
                                ->signingStation
                                ->station_token,
                    ]
                );
            }

            return redirect()
                ->route(
                    'public-consent.show',
                    $consentSession->access_token
                )
                ->withErrors([
                    'record' =>
                        'The signing deadline for this consent record has passed.',
                ]);
        }

        $cancelled = DB::transaction(
            function () use (
                $consentSession,
                $request,
                $consentAuditService,
                $consentExpiryService
            ): bool {
                $lockedSession = ConsentSession::query()
                    ->whereKey($consentSession->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $consentExpiryService->expireLockedIfDue(
                        consentSession: $lockedSession,
                        source: 'public_cancel_attempt',
                        request: $request
                    )
                ) {
                    return false;
                }

                if (
                    $lockedSession->isCompleted()
                    || $lockedSession->isCancelled()
                ) {
                    return true;
                }

                if ($lockedSession->isExpired()) {
                    return false;
                }

                $previousStatus = $lockedSession->status;
                $cancelledAt = now();

                $lockedSession->update([
                    'status' =>
                        ConsentSession::STATUS_CANCELLED,

                    'cancelled_at' =>
                        $cancelledAt,
                ]);

                $consentAuditService->record(
                    consentSession: $lockedSession,
                    eventType: 'consent.cancelled',
                    description:
                        'The signer cancelled the consent record before completion.',
                    metadata: [
                        'actor_type' =>
                            'signer',

                        'cancellation_source' =>
                            $lockedSession->signing_station_id
                                ? 'signing_station'
                                : 'direct_signing_link',

                        'signing_station_id' =>
                            $lockedSession->signing_station_id,

                        'previous_status' =>
                            $previousStatus,

                        'cancelled_at' =>
                            $cancelledAt->toIso8601String(),
                    ],
                    request: $request
                );

                return true;
            },
            3
        );

        $consentSession->refresh();
        $consentSession->loadMissing('signingStation');

        if (! $cancelled && $consentSession->isExpired()) {
            if ($consentSession->signingStation) {
                $request->session()->forget(
                    'signing_station_reviewed_'
                    .$consentSession->signingStation->id
                );

                return redirect()->route(
                    $this->stationReturnRoute($consentSession),
                    [
                        'stationToken' =>
                            $consentSession
                                ->signingStation
                                ->station_token,
                    ]
                );
            }

            return redirect()
                ->route(
                    'public-consent.show',
                    $consentSession->access_token
                )
                ->withErrors([
                    'record' =>
                        'The signing deadline for this consent record has passed.',
                ]);
        }

        if ($consentSession->signingStation) {
            $request->session()->forget(
                'signing_station_reviewed_'
                .$consentSession->signingStation->id
            );

            return redirect()->route(
                $this->stationReturnRoute($consentSession),
                [
                    'stationToken' =>
                        $consentSession
                            ->signingStation
                            ->station_token,
                ]
            );
        }

        return redirect('/');
    }

    /**
     * Display the completion confirmation page.
     */
    public function completed(
        Request $request,
        string $accessToken,
        ConsentExpiryService $consentExpiryService
    ): View|RedirectResponse {
        $consentSession = $this->findSession(
            accessToken: $accessToken,
            consentExpiryService: $consentExpiryService,
            request: $request,
            source: 'public_completion_page'
        );

        if (! $consentSession->isCompleted()) {
            return redirect()
                ->route(
                    'public-consent.show',
                    $consentSession->access_token
                );
        }

        $consentSession->load([
            'organization',
            'consentTemplate',
            'consentTemplateVersion',
            'signature',
        ]);

        return view('public-consent.completed', [
            'consentSession' => $consentSession,
        ]);
    }

    /**
     * Return QR signers to the reusable QR entry rather than
     * placing their personal device into shared-kiosk mode.
     */
    private function stationReturnRoute(
        ConsentSession $consentSession
    ): string {
        if (
            $consentSession->signing_channel ===
                ConsentSession::SIGNING_CHANNEL_QR_SCAN
        ) {
            return 'public-signing-stations.scan';
        }

        return 'public-signing-stations.show';
    }

    /**
     * Locate a consent record using its secure access token.
     */
    private function findSession(
        string $accessToken,
        ConsentExpiryService $consentExpiryService,
        ?Request $request = null,
        string $source = 'public_access'
    ): ConsentSession {
        $consentSession = ConsentSession::query()
            ->where('access_token', $accessToken)
            ->firstOrFail();

        $consentExpiryService->expireIfDue(
            consentSession: $consentSession,
            source: $source,
            request: $request
        );

        return $consentSession->refresh();
    }


}

