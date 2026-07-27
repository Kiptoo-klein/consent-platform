<?php

namespace App\Http\Controllers;


use App\Services\SigningStationFlowTracker;
use App\Jobs\GenerateConsentPdfJob;
use App\Models\ConsentSession;
use App\Models\ConsentSignature;
use App\Services\ConsentAuditService;
use App\Services\ConsentExpiryService;
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
        ConsentExpiryService $consentExpiryService
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
            'signature',
        ]);

        return view('public-consent.show', [
            'consentSession' => $consentSession,
            'publishedVersion' => $consentSession->consentTemplateVersion,
            'additionalFields' => $this->additionalFields(
                $consentSession
            ),
        ]);
    }

    /**
     * Save consent responses and move the record into progress.
     */
    public function update(
        Request $request,
        string $accessToken,
        ConsentExpiryService $consentExpiryService
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

        $additionalFields = $this->additionalFields(
            $consentSession
        );

        $validationRules = $this->buildValidationRules(
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
        ConsentExpiryService $consentExpiryService
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
            $consentExpiryService
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
                    'public-signing-stations.show',
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
                    'public-signing-stations.show',
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
                'public-signing-stations.show',
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

    /**
     * Read dynamic fields from the published template version.
     */
    private function additionalFields(
        ConsentSession $consentSession
    ): array {
        $version = $consentSession->consentTemplateVersion;

        $fields = data_get(
            $version,
            'template_schema.additional_fields',
            []
        );

        if (! is_array($fields)) {
            return [];
        }

        return array_values(
            array_filter(
                $fields,
                fn ($field) => is_array($field)
            )
        );
    }

    /**
     * Build response validation rules dynamically.
     */
    private function buildValidationRules(
        array $additionalFields
    ): array {
        $rules = [
            'responses' => [
                'nullable',
                'array',
            ],
        ];

        foreach ($additionalFields as $index => $field) {
            $key = $this->fieldKey(
                $field,
                $index
            );

            $fieldRules = [];

            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            $type = $field['type'] ?? 'text';

            switch ($type) {
                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:255';
                    break;

                case 'number':
                    $fieldRules[] = 'numeric';
                    break;

                case 'date':
                    $fieldRules[] = 'date';
                    break;

                case 'checkbox':
                    $fieldRules[] = 'boolean';
                    break;

                case 'select':
                case 'radio':
                    $fieldRules[] = 'string';

                    $options = $field['options'] ?? [];

                    if (
                        is_array($options)
                        && count($options) > 0
                    ) {
                        $fieldRules[] = 'in:'.implode(
                            ',',
                            array_map(
                                'strval',
                                $options
                            )
                        );
                    }

                    break;

                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:5000';
                    break;

                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:1000';
                    break;
            }

            $rules["responses.{$key}"] =
                $fieldRules;
        }

        return $rules;
    }

    /**
     * Determine the storage key for a dynamic field.
     */
    private function fieldKey(
        array $field,
        int $index
    ): string {
        return (string) (
            $field['name']
            ?? $field['key']
            ?? $field['id']
            ?? 'field_'.$index
        );
    }
}

