<?php

namespace App\Http\Controllers;

use App\Models\ConsentNotification;
use App\Models\ConsentPdfDelivery;
use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\SigningStation;
use App\Services\ConsentAuditService;
use App\Services\ConsentExpiryService;
use App\Services\ConsentNotificationService;
use App\Services\EvaluationEmailCreditService;
use App\Services\SubscriptionUsageLimitService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConsentSessionController extends Controller
{
    /**
     * Display consent records belonging to the current organization.
     */
    public function index(
        Request $request,
        SubscriptionUsageLimitService $usageLimitService
    ): View {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'template_id' => [
                'nullable',
                'integer',
            ],

            'category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'string',
                'in:pending,in_progress,completed,cancelled,expired',
            ],

            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'signing_station_id' => [
                'nullable',
                'integer',
            ],

            'sort' => [
                'nullable',
                'string',
                'in:newest,oldest',
            ],
        ]);

        $organizationId =
            (int) Auth::user()->organization_id;

        $signedConsentCapacity =
            $usageLimitService
                ->signedConsentCapacity(
                    $organizationId
                );

        $search = isset($validated['search'])
            ? trim($validated['search'])
            : null;

        if ($search === '') {
            $search = null;
        }

        $templateId = isset($validated['template_id'])
            ? (int) $validated['template_id']
            : null;

        $category = isset($validated['category'])
            ? trim($validated['category'])
            : null;

        if ($category === '') {
            $category = null;
        }

        $status = isset($validated['status'])
            ? trim($validated['status'])
            : null;

        if ($status === '') {
            $status = null;
        }

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        if (
            $dateFrom !== null
            && $dateTo !== null
            && $dateTo < $dateFrom
        ) {
            throw ValidationException::withMessages([
                'date_to' =>
                    'The end date must be on or after the start date.',
            ]);
        }

        $signingStationId =
            isset($validated['signing_station_id'])
                ? (int) $validated['signing_station_id']
                : null;

        $sort = $validated['sort'] ?? 'newest';

        $templates = ConsentTemplate::query()
            ->where('organization_id', $organizationId)
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'category',
            ]);

        if (
            $templateId !== null
            && ! $templates->contains(
                fn (ConsentTemplate $template): bool =>
                    (int) $template->id === $templateId
            )
        ) {
            abort(403);
        }

        $signingStations = SigningStation::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        if (
            $signingStationId !== null
            && ! $signingStations->contains(
                fn (SigningStation $station): bool =>
                    (int) $station->id === $signingStationId
            )
        ) {
            abort(403);
        }

        $statuses = [
            ConsentSession::STATUS_PENDING =>
                'Pending',

            ConsentSession::STATUS_IN_PROGRESS =>
                'In progress',

            ConsentSession::STATUS_COMPLETED =>
                'Completed',

            ConsentSession::STATUS_CANCELLED =>
                'Cancelled',

            ConsentSession::STATUS_EXPIRED =>
                'Expired',
        ];

        $filteredQuery = $this->filteredConsentSessionsQuery(
            organizationId: $organizationId,
            templateId: $templateId,
            category: $category,
            search: $search,
            status: $status,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            signingStationId: $signingStationId
        );

        $recordsQuery = (clone $filteredQuery)
            ->with([
                'consentTemplate',
                'consentTemplateVersion',
                'creator',
                'signingStation',
                'signature',
            ]);

        if ($sort === 'oldest') {
            $recordsQuery
                ->orderBy('created_at')
                ->orderBy('id');
        } else {
            $recordsQuery
                ->orderByDesc('created_at')
                ->orderByDesc('id');
        }

        $consentSessions = $recordsQuery
            ->paginate(15)
            ->withQueryString();

        $downloadableCount = (clone $filteredQuery)
            ->where(
                'status',
                ConsentSession::STATUS_COMPLETED
            )
            ->whereNotNull('pdf_path')
            ->whereNotNull('pdf_generated_at')
            ->count();

        $categories = $templates
            ->pluck('category')
            ->filter(
                fn ($value): bool =>
                    is_string($value)
                    && trim($value) !== ''
            )
            ->map(
                fn (string $value): string => trim($value)
            )
            ->unique()
            ->sort()
            ->values();

        $filters = [
            'search' => $search,
            'template_id' => $templateId,
            'category' => $category,
            'status' => $status,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'signing_station_id' =>
                $signingStationId,
            'sort' => $sort,
        ];

        $downloadParameters = array_filter(
            $filters,
            fn ($value, string $key): bool =>
                $value !== null
                && $value !== ''
                && ! (
                    $key === 'sort'
                    && $value === 'newest'
                ),
            ARRAY_FILTER_USE_BOTH
        );

        $activeFilters = [];

        if ($search !== null) {
            $activeFilters[] = [
                'label' => 'Search',
                'value' => $search,
            ];
        }

        if ($templateId !== null) {
            $selectedTemplate = $templates->firstWhere(
                'id',
                $templateId
            );

            $activeFilters[] = [
                'label' => 'Template',
                'value' =>
                    $selectedTemplate?->title
                    ?? 'Selected template',
            ];
        }

        if ($category !== null) {
            $activeFilters[] = [
                'label' => 'Category',
                'value' => $category,
            ];
        }

        if ($status !== null) {
            $activeFilters[] = [
                'label' => 'Status',
                'value' =>
                    $statuses[$status]
                    ?? ucfirst(
                        str_replace('_', ' ', $status)
                    ),
            ];
        }

        if ($dateFrom !== null) {
            $activeFilters[] = [
                'label' => 'From',
                'value' => $dateFrom,
            ];
        }

        if ($dateTo !== null) {
            $activeFilters[] = [
                'label' => 'To',
                'value' => $dateTo,
            ];
        }

        if ($signingStationId !== null) {
            $selectedStation =
                $signingStations->firstWhere(
                    'id',
                    $signingStationId
                );

            $activeFilters[] = [
                'label' => 'Station',
                'value' =>
                    $selectedStation?->name
                    ?? 'Selected station',
            ];
        }

        if ($sort === 'oldest') {
            $activeFilters[] = [
                'label' => 'Order',
                'value' => 'Oldest first',
            ];
        }

        return view('consent-sessions.index', [
            'consentSessions' => $consentSessions,
            'templates' => $templates,
            'categories' => $categories,
            'signingStations' => $signingStations,
            'statuses' => $statuses,
            'filters' => $filters,
            'activeFilters' => $activeFilters,
            'downloadParameters' =>
                $downloadParameters,
            'downloadableCount' =>
                $downloadableCount,
            'hasAnyRecords' => ConsentSession::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->exists(),
            'hasFilters' => $activeFilters !== [],
            'signedConsentCapacity' =>
                $signedConsentCapacity,
        ]);
    }

    /**
     * Display one consent record and its signing evidence.
     */
    public function show(
        Request $request,
        ConsentSession $consentSession,
        ConsentExpiryService $consentExpiryService
    ): View {
        $this->ensureSessionBelongsToOrganization(
            $consentSession
        );

        $consentExpiryService->expireIfDue(
            consentSession: $consentSession,
            source: 'organization_record_view',
            request: $request
        );

        $consentSession->refresh();

        $consentSession->load([
            'organization',
            'consentTemplate',
            'consentTemplateVersion.publisher',
            'creator',
            'signingStation',
            'signature',
        ]);

        $publishedVersion =
            $consentSession->consentTemplateVersion;

        $additionalFields = $this->additionalFields(
            $consentSession
        );

        $responseEvidence = $this->responseEvidence(
            $consentSession,
            $additionalFields
        );

        $consentText =
            data_get(
                $publishedVersion,
                'consent_text'
            )
            ?? data_get(
                $publishedVersion,
                'template_schema.consent_text'
            )
            ?? data_get(
                $publishedVersion,
                'template_schema.content'
            )
            ?? data_get(
                $publishedVersion,
                'content'
            );

        $consentNotifications = ConsentNotification::query()
            ->where('consent_session_id', $consentSession->id)
            ->latest('created_at')
            ->limit(20)
            ->get();

        $consentPdfDeliveryFeatureReady =
            Schema::hasTable('consent_pdf_deliveries');

        $consentPdfDelivery = $consentPdfDeliveryFeatureReady
            ? ConsentPdfDelivery::query()
                ->where(
                    'consent_session_id',
                    $consentSession->id
                )
                ->first()
            : null;

        return view('consent-sessions.show', [
            'consentSession' => $consentSession,
            'publishedVersion' => $publishedVersion,
            'additionalFields' => $additionalFields,
            'responseEvidence' => $responseEvidence,
            'consentText' => $consentText,
            'consentNotifications' => $consentNotifications,
            'consentPdfDelivery' => $consentPdfDelivery,
            'consentPdfDeliveryFeatureReady' =>
                $consentPdfDeliveryFeatureReady,
        ]);
    }

    /**
     * Let an organization user choose an individual-consent template.
     */
    public function selectTemplate(
        Request $request,
        EvaluationEmailCreditService $evaluationEmailCreditService
    ): View {
        $organizationId =
            (int) Auth::user()->organization_id;

        $selfTest =
            $request->boolean('self_test')
            && $evaluationEmailCreditService
                ->isEvaluationOrganization(
                    $organizationId
                );

        $consentTemplates = ConsentTemplate::query()
            ->where(
                'organization_id',
                $organizationId
            )
            ->where('status', 'published')
            ->whereNotNull('active_version_id')
            ->with([
                'activeVersion',
            ])
            ->orderBy('title')
            ->get();

        return view('consent-sessions.select-template', [
            'consentTemplates' =>
                $consentTemplates,

            'selfTest' =>
                $selfTest,
        ]);
    }

    /**
     * Show the form for creating a consent record.
     */
    public function create(
        Request $request,
        ConsentTemplate $consentTemplate,
        SubscriptionUsageLimitService $usageLimitService,
        EvaluationEmailCreditService $evaluationEmailCreditService
    ): View|RedirectResponse {
        $this->ensureTemplateBelongsToOrganization(
            $consentTemplate
        );

        $organizationId =
            (int) Auth::user()->organization_id;

        $selfTest =
            $request->boolean('self_test')
            && $evaluationEmailCreditService
                ->isEvaluationOrganization(
                    $organizationId
                );

        $selfTestEmailCapacity =
            $selfTest
                ? $evaluationEmailCreditService
                    ->capacity(
                        $organizationId
                    )
                : null;

        $usageLimitService
            ->assertSignedConsentCreationAvailable(
                (int) Auth::user()
                    ->organization_id
            );

        if (! $consentTemplate->supportsIndividualConsent()) {
            return redirect()
                ->route('consent-sessions.select-template')
                ->withErrors([
                    'template' =>
                        'This template is reserved for public signing stations.',
                ]);
        }

        $consentTemplate->load([
            'activeVersion.publisher',
        ]);

        if ($consentTemplate->activeVersion === null) {
            return redirect()
                ->route('consent-templates.index')
                ->withErrors([
                    'template' =>
                        'A consent record can only be created from a published template.',
                ]);
        }

        if ($consentTemplate->status === 'archived') {
            return redirect()
                ->route('consent-templates.index')
                ->withErrors([
                    'template' =>
                        'A consent record cannot be created from an archived template.',
                ]);
        }

        return view('consent-sessions.create', [
            'consentTemplate' => $consentTemplate,
            'publishedVersion' =>
                $consentTemplate->activeVersion,
            'signedConsentCapacity' =>
                $usageLimitService
                    ->signedConsentCapacity(
                        $organizationId
                    ),

            'selfTest' =>
                $selfTest,

            'selfTestEmailCapacity' =>
                $selfTestEmailCapacity,
        ]);
    }

    /**
     * Store a new consent record.
     */
    public function store(
        Request $request,
        ConsentTemplate $consentTemplate,
        ConsentAuditService $consentAuditService,
        ConsentNotificationService $consentNotificationService,
        SubscriptionUsageLimitService $usageLimitService,
        EvaluationEmailCreditService $evaluationEmailCreditService
    ): RedirectResponse {
        $this->ensureTemplateBelongsToOrganization(
            $consentTemplate
        );

        $organizationId =
            (int) Auth::user()->organization_id;

        $selfTest =
            $request->boolean('self_test')
            && $evaluationEmailCreditService
                ->isEvaluationOrganization(
                    $organizationId
                );

        if ($selfTest) {
            $emailCapacity =
                $evaluationEmailCreditService
                    ->capacity(
                        $organizationId
                    );

            if ($emailCapacity['reached']) {
                throw ValidationException::withMessages([
                    'email' =>
                        'All 5 Free Evaluation invitation emails '
                        .'have been used. Choose a paid plan to '
                        .'send more signing emails.',
                ]);
            }

            /*
             * Never trust browser-submitted identity fields for
             * "Send test consent to myself".
             */
            $request->merge([
                'signer_name' =>
                    Auth::user()->name,

                'signer_email' =>
                    Auth::user()->email,
            ]);
        }

        if (! $consentTemplate->supportsIndividualConsent()) {
            throw ValidationException::withMessages([
                'template' =>
                    'This template is reserved for public signing stations.',
            ]);
        }

        $validated = $request->validate([
            'self_test' => [
                'nullable',
                'boolean',
            ],

            'signer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'signer_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'signer_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'expires_date' => [
                'nullable',
                'required_with:expires_time',
                'date_format:Y-m-d',
            ],

            'expires_time' => [
                'nullable',
                Rule::prohibitedIf(fn (): bool => blank($request->input('expires_date'))),
                'date_format:H:i',
            ],
        ], [
            'expires_date.required_with' =>
                'Please select the signing deadline date.',

            'expires_date.date_format' =>
                'Please select a valid signing deadline date.',

            'expires_time.prohibited' =>
                'Select a deadline date before choosing a deadline time.',

            'expires_time.date_format' =>
                'Please enter a valid signing deadline time.',
        ]);

        $expiresAt = null;

        if (filled($validated['expires_date'] ?? null)) {
            $expiresTime =
                $validated['expires_time'] ?? '00:00';

            $expiresAt = CarbonImmutable::createFromFormat(
                '!Y-m-d H:i',
                $validated['expires_date'].' '.$expiresTime,
                config('app.timezone')
            );

            if (! $expiresAt->isAfter(now())) {
                throw ValidationException::withMessages([
                    'expires_date' =>
                        'The signing deadline must be in the future.',
                ]);
            }
        }

        $consentSession = DB::transaction(
            function () use (
                $consentTemplate,
                $validated,
                $expiresAt,
                $request,
                $consentAuditService,
                $selfTest
            ): ConsentSession {
                /*
                |--------------------------------------------------------------------------
                | Lock the template during record creation
                |--------------------------------------------------------------------------
                |
                | This prevents the active version from changing while the
                | consent record is being created.
                */

                $lockedTemplate = ConsentTemplate::query()
                    ->whereKey($consentTemplate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureTemplateBelongsToOrganization(
                    $lockedTemplate
                );

                if (! $lockedTemplate->supportsIndividualConsent()) {
                    throw ValidationException::withMessages([
                        'template' =>
                            'This template is reserved for public signing stations.',
                    ]);
                }

                if (
                    $lockedTemplate->active_version_id === null
                ) {
                    throw ValidationException::withMessages([
                        'template' =>
                            'This template no longer has an active published version.',
                    ]);
                }

                if (
                    $lockedTemplate->status === 'archived'
                ) {
                    throw ValidationException::withMessages([
                        'template' =>
                            'A consent record cannot be created from an archived template.',
                    ]);
                }

                $consentSession = ConsentSession::query()->create([
                    'organization_id' =>
                        Auth::user()->organization_id,

                    'consent_template_id' =>
                        $lockedTemplate->id,

                    'consent_template_version_id' =>
                        $lockedTemplate->active_version_id,

                    'created_by' =>
                        Auth::id(),

                    'signer_name' =>
                        $validated['signer_name'],

                    'signer_email' =>
                        $validated['signer_email'] ?? null,

                    'signer_reference' =>
                        $validated['signer_reference'] ?? null,

                    'access_token' =>
                        (string) Str::uuid(),

                    'status' =>
                        ConsentSession::STATUS_PENDING,

                    'responses' =>
                        [],

                    'expires_at' =>
                        $expiresAt,

                    'expired_at' =>
                        null,
                ]);

                $consentAuditService->record(
                    consentSession: $consentSession,
                    eventType: 'consent.created',
                    description:
                        'The consent record was created by an organization user.',
                    metadata: [
                        'creation_source' =>
                            $selfTest
                                ? 'evaluation_self_test'
                                : 'organization_user',

                        'consent_template_id' =>
                            $lockedTemplate->id,

                        'consent_template_version_id' =>
                            $lockedTemplate->active_version_id,

                        'initial_status' =>
                            ConsentSession::STATUS_PENDING,

                        'expires_at' =>
                            $expiresAt?->toIso8601String(),
                    ],
                    request: $request
                );

                return $consentSession;
            },
            3
        );

        $emailDelivery = null;

        if (filled($consentSession->signer_email)) {
            $emailDelivery =
                $consentNotificationService->sendInitial(
                    consentSession: $consentSession,
                    actorUserId: Auth::id(),
                    trigger: ConsentNotification::TRIGGER_AUTOMATIC_CREATION
                );
        }

        $redirect = redirect()
            ->route(
                'consent-sessions.show',
                $consentSession
            )
            ->with(
                'success',
                "Consent record for {$consentSession->signer_name} created successfully."
            );

        if (
            $emailDelivery !== null
            && ! $emailDelivery->isFailed()
        ) {
            $redirect->with(
                'email_success',
                "The secure signing link was emailed to {$consentSession->signer_email}."
            );
        } elseif ($emailDelivery?->isFailed()) {
            $redirect->with(
                'email_warning',
                'The consent record was created, but the signing email could not be delivered. Check the mail settings and use Send email again.'
            );
        }

        return $redirect;
    }

    /**
     * Cancel an unfinished consent record.
     */
    public function cancel(
        Request $request,
        ConsentSession $consentSession,
        ConsentAuditService $consentAuditService,
        ConsentExpiryService $consentExpiryService
    ): RedirectResponse {
        $this->ensureSessionBelongsToOrganization(
            $consentSession
        );

        $consentExpiryService->expireIfDue(
            consentSession: $consentSession,
            source: 'organization_cancel_attempt',
            request: $request
        );

        $consentSession->refresh();

        if ($consentSession->isCompleted()) {
            return back()->withErrors([
                'record' =>
                    'A completed consent record cannot be cancelled.',
            ]);
        }

        if ($consentSession->isCancelled()) {
            return back()->withErrors([
                'record' =>
                    'This consent record has already been cancelled.',
            ]);
        }

        if ($consentSession->isExpired()) {
            return back()->withErrors([
                'record' =>
                    'An expired consent record cannot be cancelled.',
            ]);
        }

        $cancelledSession = DB::transaction(
            function () use (
                $request,
                $consentSession,
                $consentAuditService,
                $consentExpiryService
            ): ConsentSession {
                $lockedSession = ConsentSession::query()
                    ->whereKey($consentSession->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureSessionBelongsToOrganization(
                    $lockedSession
                );

                if (
                    $consentExpiryService->expireLockedIfDue(
                        consentSession: $lockedSession,
                        source: 'organization_cancel_attempt',
                        request: $request
                    )
                ) {
                    return $lockedSession;
                }

                if ($lockedSession->isCompleted()) {
                    throw ValidationException::withMessages([
                        'record' =>
                            'A completed consent record cannot be cancelled.',
                    ]);
                }

                if ($lockedSession->isCancelled()) {
                    throw ValidationException::withMessages([
                        'record' =>
                            'This consent record has already been cancelled.',
                    ]);
                }

                if ($lockedSession->isExpired()) {
                    return $lockedSession;
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
                        'An organization user cancelled the consent record.',
                    metadata: [
                        'cancellation_source' =>
                            'organization_application',

                        'previous_status' =>
                            $previousStatus,

                        'cancelled_at' =>
                            $cancelledAt->toIso8601String(),
                    ],
                    request: $request
                );

                return $lockedSession;
            },
            3
        );

        if ($cancelledSession->isExpired()) {
            return redirect()
                ->route(
                    'consent-sessions.show',
                    $cancelledSession
                )
                ->withErrors([
                    'record' =>
                        'The signing deadline passed before the record could be cancelled.',
                ]);
        }

        return redirect()
            ->route(
                'consent-sessions.show',
                $cancelledSession
            )
            ->with(
                'success',
                'The consent record was cancelled successfully.'
            );
    }


    /**
     * Build a tenant-safe record query using the current filters.
     */
    private function filteredConsentSessionsQuery(
        int $organizationId,
        ?int $templateId,
        ?string $category,
        ?string $search,
        ?string $status,
        ?string $dateFrom,
        ?string $dateTo,
        ?int $signingStationId
    ): Builder {
        return ConsentSession::query()
            ->where(
                'organization_id',
                $organizationId
            )
            ->when(
                $search !== null,
                function (
                    Builder $query
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $searchQuery
                        ) use ($search): void {
                            if (ctype_digit($search)) {
                                $searchQuery->where(
                                    'id',
                                    (int) $search
                                );
                            } else {
                                $searchQuery->where(
                                    'signer_name',
                                    'like',
                                    '%'.$search.'%'
                                );
                            }

                            $searchQuery
                                ->orWhere(
                                    'signer_name',
                                    'like',
                                    '%'.$search.'%'
                                )
                                ->orWhere(
                                    'signer_email',
                                    'like',
                                    '%'.$search.'%'
                                )
                                ->orWhere(
                                    'signer_reference',
                                    'like',
                                    '%'.$search.'%'
                                );
                        }
                    );
                }
            )
            ->when(
                $templateId !== null,
                fn (Builder $query): Builder =>
                    $query->where(
                        'consent_template_id',
                        $templateId
                    )
            )
            ->when(
                $category !== null,
                fn (Builder $query): Builder =>
                    $query->whereHas(
                        'consentTemplate',
                        fn (
                            Builder $templateQuery
                        ): Builder =>
                            $templateQuery->where(
                                'category',
                                $category
                            )
                    )
            )
            ->when(
                $status !== null,
                fn (Builder $query): Builder =>
                    $query->where(
                        'status',
                        $status
                    )
            )
            ->when(
                $dateFrom !== null,
                fn (Builder $query): Builder =>
                    $query->whereDate(
                        'created_at',
                        '>=',
                        $dateFrom
                    )
            )
            ->when(
                $dateTo !== null,
                fn (Builder $query): Builder =>
                    $query->whereDate(
                        'created_at',
                        '<=',
                        $dateTo
                    )
            )
            ->when(
                $signingStationId !== null,
                fn (Builder $query): Builder =>
                    $query->where(
                        'signing_station_id',
                        $signingStationId
                    )
            );
    }

    /**
     * Read dynamic fields from the exact version
     * linked to this consent record.
     */
    private function additionalFields(
        ConsentSession $consentSession
    ): array {
        $fields = data_get(
            $consentSession->consentTemplateVersion,
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
     * Match stored response keys to their original field
     * labels and types from the published version.
     */
    private function responseEvidence(
        ConsentSession $consentSession,
        array $additionalFields
    ): array {
        $responses = $consentSession->responses ?? [];

        if (! is_array($responses)) {
            return [];
        }

        $fieldDefinitions = [];

        foreach ($additionalFields as $index => $field) {
            $key = $this->fieldKey(
                $field,
                $index
            );

            $fieldDefinitions[$key] = [
                'label' =>
                    $field['label']
                    ?? $this->humanizeKey($key),

                'type' =>
                    $field['type'] ?? 'text',
            ];
        }

        $evidence = [];

        foreach ($responses as $key => $value) {
            $stringKey = (string) $key;

            $definition =
                $fieldDefinitions[$stringKey] ?? null;

            $evidence[] = [
                'key' =>
                    $stringKey,

                'label' =>
                    $definition['label']
                    ?? $this->humanizeKey($stringKey),

                'type' =>
                    $definition['type']
                    ?? 'text',

                'value' =>
                    $value,
            ];
        }

        return $evidence;
    }

    /**
     * Determine the request/storage key for a dynamic field.
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

    /**
     * Turn a response key into a readable fallback label.
     */
    private function humanizeKey(
        string $key
    ): string {
        return Str::of($key)
            ->replace([
                '_',
                '-',
            ], ' ')
            ->squish()
            ->title()
            ->toString();
    }

    /**
     * Prevent access to another organization's template.
     */
    private function ensureTemplateBelongsToOrganization(
        ConsentTemplate $consentTemplate
    ): void {
        abort_unless(
            (int) $consentTemplate->organization_id ===
                (int) Auth::user()->organization_id,
            403
        );
    }

    /**
     * Prevent access to another organization's consent record.
     */
    private function ensureSessionBelongsToOrganization(
        ConsentSession $consentSession
    ): void {
        abort_unless(
            (int) $consentSession->organization_id ===
                (int) Auth::user()->organization_id,
            403
        );
    }
}


