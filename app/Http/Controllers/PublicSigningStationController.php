<?php

namespace App\Http\Controllers;


use App\Services\SigningStationFlowTracker;
use App\Models\ConsentSession;
use App\Models\SigningStation;
use App\Services\ConsentAuditService;
use App\Services\SubscriptionUsageLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicSigningStationController extends Controller
{
    /**
     * Display the signing-station welcome page.
     */
    public function show(
        string $stationToken,
        SubscriptionUsageLimitService $usageLimitService
    ): View {
        $station = $this->findStation($stationToken);

        if (
            $usageLimitService
                ->signedConsentCapacity(
                    (int) $station
                        ->organization_id
                )['reached']
        ) {
            return view(
                'public-signing-stations.signed-consent-limit-reached',
                compact('station')
            );
        }

        return view('public-signing-stations.show', [
            'station' => $station,
            'organization' => $station->organization,
            'publishedVersion' => $station->consentTemplate?->latestVersion,
        ]);
    }

    /**
     * Display the consent document for review.
     */
    public function review(
        Request $request,
        string $stationToken,
        SubscriptionUsageLimitService $usageLimitService
    ): View {
        $station = $this->findStation($stationToken);

        if (
            $usageLimitService
                ->signedConsentCapacity(
                    (int) $station
                        ->organization_id
                )['reached']
        ) {
            return view(
                'public-signing-stations.signed-consent-limit-reached',
                compact('station')
            );
        }

        // KIOSK_ANALYTICS_REVIEW_STARTED
        app(SigningStationFlowTracker::class)
            ->beginReview($request, $station);

        $publishedVersion = $station->consentTemplate?->latestVersion;

        abort_if(
            ! $publishedVersion,
            404,
            'This consent template does not have a published version.'
        );

        return view('public-signing-stations.review', [
            'station' => $station,
            'organization' => $station->organization,
            'publishedVersion' => $publishedVersion,
        ]);
    }

    /**
     * Confirm that the signer reviewed the document.
     */
    public function confirmReview(
        Request $request,
        string $stationToken,
        SubscriptionUsageLimitService $usageLimitService
    ): RedirectResponse {
        $station = $this->findStation($stationToken);

        if (
            $usageLimitService
                ->signedConsentCapacity(
                    (int) $station
                        ->organization_id
                )['reached']
        ) {
            return redirect()->route(
                'public-signing-stations.show',
                [
                    'stationToken' =>
                        $station
                            ->station_token,
                ]
            );
        }

        $request->validate(
            [
                'review_confirmed' => [
                    'accepted',
                ],
            ],
            [
                'review_confirmed.accepted' =>
                    'Please confirm that you have reviewed the consent document.',
            ]
        );

        // KIOSK_ANALYTICS_REVIEW_CONFIRMED
        app(SigningStationFlowTracker::class)
            ->confirmReview($request, $station);

        $request->session()->put(
            $this->reviewSessionKey($station),
            true
        );

        return redirect()->route(
            'public-signing-stations.details',
            [
                'stationToken' => $station->station_token,
            ]
        );
    }

    /**
     * Display the signer-details page.
     */
    public function details(
        Request $request,
        string $stationToken,
        SubscriptionUsageLimitService $usageLimitService
    ): View|RedirectResponse {
        $station = $this->findStation($stationToken);

        if (
            $usageLimitService
                ->signedConsentCapacity(
                    (int) $station
                        ->organization_id
                )['reached']
        ) {
            return view(
                'public-signing-stations.signed-consent-limit-reached',
                compact('station')
            );
        }

        if (! $this->hasReviewedDocument($request, $station)) {
            return redirect()
                ->route(
                    'public-signing-stations.review',
                    [
                        'stationToken' => $station->station_token,
                    ]
                )
                ->withErrors([
                    'review_confirmed' =>
                        'Please review and confirm the consent document before continuing.',
                ]);
        }

        // KIOSK_ANALYTICS_DETAILS_STARTED
        app(SigningStationFlowTracker::class)
            ->touchDetails($request, $station);

        $publishedVersion = $station->consentTemplate?->latestVersion;

        abort_if(
            ! $publishedVersion,
            404,
            'This consent template does not have a published version.'
        );

        return view('public-signing-stations.details', [
            'station' => $station,
            'organization' => $station->organization,
            'publishedVersion' => $publishedVersion,
            'additionalFields' => $this->additionalFields(
                $publishedVersion
            ),
        ]);
    }

    /**
     * Validate signer details, create the consent session,
     * record the creation audit event, and redirect to signing.
     */
    public function start(
        Request $request,
        string $stationToken,
        ConsentAuditService $consentAuditService,
        SubscriptionUsageLimitService $usageLimitService
    ): RedirectResponse {
        $station = $this->findStation($stationToken);

        if (
            $usageLimitService
                ->signedConsentCapacity(
                    (int) $station
                        ->organization_id
                )['reached']
        ) {
            return redirect()->route(
                'public-signing-stations.show',
                [
                    'stationToken' =>
                        $station
                            ->station_token,
                ]
            );
        }

        if (! $this->hasReviewedDocument($request, $station)) {
            return redirect()->route(
                'public-signing-stations.review',
                [
                    'stationToken' => $station->station_token,
                ]
            );
        }

        $publishedVersion = $station->consentTemplate?->latestVersion;

        abort_if(
            ! $publishedVersion,
            404,
            'This consent template does not have a published version.'
        );

        $additionalFields = $this->additionalFields(
            $publishedVersion
        );

        $validationRules = $this->buildValidationRules(
            $station,
            $additionalFields
        );

        $validated = $request->validate(
            $validationRules,
            [
                'signer_name.required' =>
                    'Please enter your full name.',

                'signer_email.required' =>
                    'Please enter your email address.',

                'signer_email.email' =>
                    'Please enter a valid email address.',
            ]
        );

        $consentSession = DB::transaction(
            function () use (
                $station,
                $publishedVersion,
                $validated,
                $additionalFields,
                $consentAuditService,
                $request
            ): ConsentSession {
                $createdSession = ConsentSession::query()->create([
                    'organization_id' =>
                        $station->organization_id,

                    'created_by' =>
                        $station->created_by,

                    'consent_template_id' =>
                        $station->consent_template_id,

                    'consent_template_version_id' =>
                        $publishedVersion->id,

                    'signing_station_id' =>
                        $station->id,

                    'access_token' =>
                        (string) Str::uuid(),

                    'signer_name' =>
                        $validated['signer_name'],

                    'signer_email' =>
                        $validated['signer_email'] ?? null,
    'signer_reference' => null,

                    'responses' =>
                        $this->selectedResponses(
                            $validated,
                            $additionalFields
                        ),

                    'status' =>
                        ConsentSession::STATUS_IN_PROGRESS,

                    'started_at' =>
                        now(),

                    'completed_at' =>
                        null,

                    'cancelled_at' =>
                        null,
                ]);

                $consentAuditService->record(
                    consentSession: $createdSession,
                    eventType: 'consent.created',
                    description:
                        'The consent record was created through a public signing station.',
                    metadata: [
                        'source' =>
                            'signing_station',

                        'signing_station_id' =>
                            $station->id,

                        'consent_template_id' =>
                            $station->consent_template_id,

                        'consent_template_version_id' =>
                            $publishedVersion->id,
                    ],
                    request: $request
                );

                return $createdSession;
            },
            3
        );

        // KIOSK_ANALYTICS_CONSENT_STARTED
        app(SigningStationFlowTracker::class)->attachConsent(
            $request,
            $station,
            $consentSession
        );

        $request->session()->forget(
            $this->reviewSessionKey($station)
        );

        return redirect()->route(
            'public-consent.signature',
            [
                'accessToken' =>
                    $consentSession->access_token,
            ]
        );
    }

    /**
     * Cancel the current station flow and return to the welcome page.
     */
    public function cancel(
        Request $request,
        string $stationToken
    ): RedirectResponse {
        $station = $this->findStation($stationToken);

        // KIOSK_ANALYTICS_STATION_CANCELLED
        app(SigningStationFlowTracker::class)->cancelStationFlow(
            $request,
            $station,
            $request->boolean('timed_out')
        );

        $request->session()->forget(
            $this->reviewSessionKey($station)
        );

        return redirect()->route(
            'public-signing-stations.show',
            [
                'stationToken' => $station->station_token,
            ]
        );
    }

    /**
     * Locate a public signing station using its secure token.
     */
    private function findStation(
        string $stationToken
    ): SigningStation {
        $station = SigningStation::query()
            ->with([
                'organization',
                'consentTemplate.latestVersion',
            ])
            ->where(
                'station_token',
                $stationToken
            )
            ->firstOrFail();

        abort_if(
            ! $station->active,
            404,
            'This signing station is currently unavailable.'
        );

        abort_if(
            ! $station->consentTemplate,
            404,
            'This signing station does not have a consent template.'
        );

        abort_if(
            ! $station->consentTemplate->isLive(),
            404,
            'The consent template for this station is not currently available.'
        );

        return $station;
    }

    /**
     * Build the session key used to remember document review.
     */
    private function reviewSessionKey(
        SigningStation $station
    ): string {
        return 'signing_station_reviewed_'.$station->id;
    }

    /**
     * Check whether the signer confirmed document review.
     */
    private function hasReviewedDocument(
        Request $request,
        SigningStation $station
    ): bool {
        return (bool) $request->session()->get(
            $this->reviewSessionKey($station),
            false
        );
    }

    /**
     * Read additional fields from the published template version.
     */
    private function additionalFields(
        object $publishedVersion
    ): array {
        $fields = data_get(
            $publishedVersion,
            'template_schema.additional_fields',
            []
        );

        if (! is_array($fields)) {
            return [];
        }

        return array_values(
            array_filter(
                $fields,
                fn ($field): bool => is_array($field)
            )
        );
    }

    /**
     * Return only organization-defined fields that are required.
     */
    private function requiredAdditionalFields(
        object $publishedVersion
    ): array {
        return array_values(
            array_filter(
                $this->additionalFields($publishedVersion),
                fn (array $field): bool =>
                    (bool) ($field['required'] ?? false)
            )
        );
    }

    /**
     * Keep only responses for the selected fields displayed
     * during this signing-station flow.
     */
    private function selectedResponses(
        array $validated,
        array $additionalFields
    ): array {
        $submittedResponses =
            $validated['responses'] ?? [];

        if (! is_array($submittedResponses)) {
            return [];
        }

        $responses = [];

        foreach ($additionalFields as $index => $field) {
            $key = $this->fieldKey(
                $field,
                $index
            );

            if (array_key_exists($key, $submittedResponses)) {
                $responses[$key] =
                    $submittedResponses[$key];
            }
        }

        return $responses;
    }

    /**
     * Build validation rules for standard and dynamic fields.
     */
    private function buildValidationRules(
        SigningStation $station,
        array $additionalFields
    ): array {
        $rules = [
            'signer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'signer_email' => [
                $station->require_email
                    ? 'required'
                    : 'nullable',

                'email',
                'max:255',
            ],
            'signer_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

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

            $type = $field['type'] ?? 'text';

            if ($type === 'checkbox') {
                $fieldRules = ! empty($field['required'])
                    ? ['accepted']
                    : [
                        'nullable',
                        'boolean',
                    ];
            } else {
                $fieldRules = [
                    ! empty($field['required'])
                        ? 'required'
                        : 'nullable',
                ];
            }

            if ($type === 'email') {
                $fieldRules[] = 'email';
                $fieldRules[] = 'max:255';
            } elseif ($type === 'number') {
                $fieldRules[] = 'numeric';
            } elseif ($type === 'checkbox') {
                // Checkbox rules were assigned above.
            } elseif (
                in_array(
                    $type,
                    [
                        'select',
                        'radio',
                    ],
                    true
                )
            ) {
                $options = $field['options'] ?? [];

                if (is_array($options) && $options !== []) {
                    $fieldRules[] = Rule::in(
                        array_values($options)
                    );
                }
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:5000';
            }

            $rules['responses.'.$key] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Determine the request key for an additional field.
     */
    private function fieldKey(
        array $field,
        int $index
    ): string {
        $key = $field['name']
            ?? $field['key']
            ?? $field['id']
            ?? 'field_'.$index;

        return (string) $key;
    }
}

