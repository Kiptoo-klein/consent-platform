<?php

namespace App\Http\Controllers;

use App\Models\ConsentCampaign;
use App\Models\ConsentNotification;
use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Services\ConsentAuditService;
use App\Services\ConsentNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class ConsentCampaignController extends Controller
{
    public function index(
        Request $request
    ): View {
        $organizationId =
            (int) $request
                ->user()
                ->organization_id;

        $campaigns =
            ConsentCampaign::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->with([
                    'consentTemplate:id,title',
                    'creator:id,name',
                ])
                ->withCount([
                    'consentSessions',
                    'consentSessions as pending_count' =>
                        fn ($query) =>
                            $query->where(
                                'status',
                                ConsentSession::
                                    STATUS_PENDING
                            ),

                    'consentSessions as in_progress_count' =>
                        fn ($query) =>
                            $query->where(
                                'status',
                                ConsentSession::
                                    STATUS_IN_PROGRESS
                            ),

                    'consentSessions as completed_count' =>
                        fn ($query) =>
                            $query->where(
                                'status',
                                ConsentSession::
                                    STATUS_COMPLETED
                            ),

                    'consentSessions as expired_count' =>
                        fn ($query) =>
                            $query->where(
                                'status',
                                ConsentSession::
                                    STATUS_EXPIRED
                            ),

                    'consentSessions as cancelled_count' =>
                        fn ($query) =>
                            $query->where(
                                'status',
                                ConsentSession::
                                    STATUS_CANCELLED
                            ),
                ])
                ->latest()
                ->paginate(15);

        return view(
            'consent-campaigns.index',
            [
                'campaigns' =>
                    $campaigns,
            ]
        );
    }

    public function selectTemplate(
        Request $request
    ): View {
        $organizationId =
            (int) $request
                ->user()
                ->organization_id;

        $consentTemplates =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->where(
                    'status',
                    'published'
                )
                ->whereNotNull(
                    'active_version_id'
                )
                ->whereIn(
                    'usage_type',
                    [
                        ConsentTemplate::
                            USAGE_INDIVIDUAL,

                        ConsentTemplate::
                            USAGE_BOTH,
                    ]
                )
                ->with([
                    'activeVersion',
                ])
                ->orderBy('title')
                ->get();

        return view(
            'consent-campaigns.select-template',
            [
                'consentTemplates' =>
                    $consentTemplates,
            ]
        );
    }

    public function create(
        Request $request,
        ConsentTemplate $consentTemplate
    ): View|RedirectResponse {
        $this->ensureTemplateBelongsToOrganization(
            $request,
            $consentTemplate
        );

        $consentTemplate->load([
            'activeVersion.publisher',
        ]);

        if (
            ! $consentTemplate
                ->supportsIndividualConsent()
        ) {
            return redirect()
                ->route(
                    'consent-campaigns.select-template'
                )
                ->withErrors([
                    'template' =>
                        'This template is reserved for public signing stations.',
                ]);
        }

        if (
            $consentTemplate->activeVersion === null
            || $consentTemplate->status === 'archived'
        ) {
            return redirect()
                ->route(
                    'consent-campaigns.select-template'
                )
                ->withErrors([
                    'template' =>
                        'A bulk consent campaign requires a live published template.',
                ]);
        }

        return view(
            'consent-campaigns.create',
            [
                'consentTemplate' =>
                    $consentTemplate,

                'publishedVersion' =>
                    $consentTemplate
                        ->activeVersion,

                'maximumRecipients' =>
                    ConsentCampaign::
                        MAX_RECIPIENTS,
            ]
        );
    }

    public function store(
        Request $request,
        ConsentTemplate $consentTemplate,
        ConsentAuditService $consentAuditService,
        ConsentNotificationService $notificationService
    ): RedirectResponse {
        $this->ensureTemplateBelongsToOrganization(
            $request,
            $consentTemplate
        );

        $recipients =
            $this->manualRecipients(
                $request->input(
                    'recipients',
                    []
                )
            );

        if (
            $request->hasFile(
                'recipient_file'
            )
        ) {
            $recipients = array_merge(
                $recipients,
                $this->csvRecipients(
                    $request->file(
                        'recipient_file'
                    )
                )
            );
        }

        $request->merge([
            'recipients' =>
                $recipients,
        ]);

        $validated =
            $request->validate([
                'campaign_name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'recipient_file' => [
                    'nullable',
                    'file',
                    'max:2048',
                ],

                'recipients' => [
                    'required',
                    'array',
                    'min:1',
                    'max:'.ConsentCampaign::
                        MAX_RECIPIENTS,
                ],

                'recipients.*.name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'recipients.*.email' => [
                    'required',
                    'email',
                    'max:255',
                ],

                'recipients.*.reference' => [
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
                    Rule::prohibitedIf(
                        fn (): bool =>
                            blank(
                                $request->input(
                                    'expires_date'
                                )
                            )
                    ),
                    'date_format:H:i',
                ],
            ], [
                'recipients.required' =>
                    'Add at least one recipient or upload a CSV file.',

                'recipients.max' =>
                    'A campaign can contain no more than 20 recipients.',

                'recipients.*.name.required' =>
                    'Every recipient needs a name.',

                'recipients.*.email.required' =>
                    'Every recipient needs an email address.',

                'recipients.*.email.email' =>
                    'Every recipient must have a valid email address.',

                'expires_date.required_with' =>
                    'Please select the signing deadline date.',

                'expires_time.prohibited' =>
                    'Select a deadline date before choosing a deadline time.',
            ]);

        $normalizedRecipients =
            $this->uniqueRecipients(
                $validated['recipients']
            );

        $expiresAt =
            $this->expiresAt(
                $validated
            );

        [
            $campaign,
            $sessions,
        ] = DB::transaction(
            function () use (
                $request,
                $consentTemplate,
                $validated,
                $normalizedRecipients,
                $expiresAt,
                $consentAuditService
            ): array {
                $lockedTemplate =
                    ConsentTemplate::query()
                        ->whereKey(
                            $consentTemplate->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this
                    ->ensureTemplateBelongsToOrganization(
                        $request,
                        $lockedTemplate
                    );

                if (
                    ! $lockedTemplate
                        ->supportsIndividualConsent()
                ) {
                    throw ValidationException::
                        withMessages([
                            'template' =>
                                'This template is reserved for public signing stations.',
                        ]);
                }

                if (
                    $lockedTemplate
                        ->active_version_id
                    === null
                ) {
                    throw ValidationException::
                        withMessages([
                            'template' =>
                                'This template no longer has an active published version.',
                        ]);
                }

                if (
                    $lockedTemplate->status
                    === 'archived'
                ) {
                    throw ValidationException::
                        withMessages([
                            'template' =>
                                'A campaign cannot be created from an archived template.',
                        ]);
                }

                $organizationId =
                    (int) $request
                        ->user()
                        ->organization_id;

                $campaign =
                    ConsentCampaign::query()
                        ->create([
                            'organization_id' =>
                                $organizationId,

                            'consent_template_id' =>
                                $lockedTemplate->id,

                            'consent_template_version_id' =>
                                $lockedTemplate
                                    ->active_version_id,

                            'created_by' =>
                                Auth::id(),

                            'name' =>
                                trim(
                                    $validated[
                                        'campaign_name'
                                    ]
                                ),

                            'recipient_count' =>
                                count(
                                    $normalizedRecipients
                                ),

                            'expires_at' =>
                                $expiresAt,
                        ]);

                $sessions = collect();

                foreach (
                    $normalizedRecipients
                    as $recipient
                ) {
                    $consentSession =
                        ConsentSession::query()
                            ->create([
                                'organization_id' =>
                                    $organizationId,

                                'consent_template_id' =>
                                    $lockedTemplate->id,

                                'consent_template_version_id' =>
                                    $lockedTemplate
                                        ->active_version_id,

                                'signing_station_id' =>
                                    null,

                                'consent_campaign_id' =>
                                    $campaign->id,

                                'created_by' =>
                                    Auth::id(),

                                'signer_name' =>
                                    $recipient['name'],

                                'signer_email' =>
                                    $recipient['email'],

                                'signer_reference' =>
                                    $recipient[
                                        'reference'
                                    ],

                                'access_token' =>
                                    (string) Str::uuid(),

                                'status' =>
                                    ConsentSession::
                                        STATUS_PENDING,

                                'responses' =>
                                    [],

                                'expires_at' =>
                                    $expiresAt,

                                'expired_at' =>
                                    null,
                            ]);

                    $consentAuditService->record(
                        consentSession:
                            $consentSession,

                        eventType:
                            'consent.created',

                        description:
                            'The consent record was created as part of a bulk consent campaign.',

                        metadata: [
                            'creation_source' =>
                                'bulk_consent_campaign',

                            'consent_campaign_id' =>
                                $campaign->id,

                            'consent_campaign_name' =>
                                $campaign->name,

                            'campaign_recipient_count' =>
                                $campaign
                                    ->recipient_count,

                            'consent_template_id' =>
                                $lockedTemplate->id,

                            'consent_template_version_id' =>
                                $lockedTemplate
                                    ->active_version_id,

                            'initial_status' =>
                                ConsentSession::
                                    STATUS_PENDING,

                            'expires_at' =>
                                $expiresAt
                                    ?->toIso8601String(),
                        ],

                        request:
                            $request
                    );

                    $sessions->push(
                        $consentSession
                    );
                }

                return [
                    $campaign,
                    $sessions,
                ];
            },
            3
        );

        $sentCount = 0;
        $failedCount = 0;

        foreach ($sessions as $session) {
            $notification =
                $notificationService
                    ->sendInitial(
                        consentSession:
                            $session,

                        actorUserId:
                            Auth::id(),

                        trigger:
                            ConsentNotification::
                                TRIGGER_AUTOMATIC_CREATION
                    );

            if ($notification->isFailed()) {
                $failedCount++;
            } else {
                $sentCount++;
            }
        }

        $redirect = redirect()
            ->route(
                'consent-campaigns.show',
                $campaign
            )
            ->with(
                'success',
                "{$campaign->recipient_count} recipient consent records were created. {$sentCount} invitation emails were accepted for delivery."
            );

        if ($failedCount > 0) {
            $redirect->with(
                'email_warning',
                "{$failedCount} invitation email(s) could not be delivered. The consent records were kept so they can be retried individually."
            );
        }

        return $redirect;
    }

    public function show(
        Request $request,
        ConsentCampaign $consentCampaign
    ): View {
        $this
            ->ensureCampaignBelongsToOrganization(
                $request,
                $consentCampaign
            );

        $consentCampaign->load([
            'organization',
            'consentTemplate',
            'consentTemplateVersion.publisher',
            'creator',
            'consentSessions' =>
                fn ($query) =>
                    $query
                        ->with([
                            'notifications' =>
                                fn ($notificationQuery) =>
                                    $notificationQuery
                                        ->latest(),
                        ])
                        ->orderBy(
                            'signer_name'
                        )
                        ->orderBy('id'),
        ]);

        $statusCounts =
            $consentCampaign
                ->consentSessions
                ->countBy('status');

        return view(
            'consent-campaigns.show',
            [
                'campaign' =>
                    $consentCampaign,

                'statusCounts' => [
                    'pending' =>
                        (int) $statusCounts->get(
                            ConsentSession::
                                STATUS_PENDING,
                            0
                        ),

                    'in_progress' =>
                        (int) $statusCounts->get(
                            ConsentSession::
                                STATUS_IN_PROGRESS,
                            0
                        ),

                    'completed' =>
                        (int) $statusCounts->get(
                            ConsentSession::
                                STATUS_COMPLETED,
                            0
                        ),

                    'expired' =>
                        (int) $statusCounts->get(
                            ConsentSession::
                                STATUS_EXPIRED,
                            0
                        ),

                    'cancelled' =>
                        (int) $statusCounts->get(
                            ConsentSession::
                                STATUS_CANCELLED,
                            0
                        ),
                ],
            ]
        );
    }

    /**
     * @param mixed $value
     * @return array<int, array{name: string, email: string, reference: string}>
     */
    private function manualRecipients(
        mixed $value
    ): array {
        if (! is_array($value)) {
            return [];
        }

        $recipients = [];

        foreach ($value as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim(
                (string) (
                    $row['name']
                    ?? ''
                )
            );

            $email = trim(
                (string) (
                    $row['email']
                    ?? ''
                )
            );

            $reference = trim(
                (string) (
                    $row['reference']
                    ?? ''
                )
            );

            if (
                $name === ''
                && $email === ''
                && $reference === ''
            ) {
                continue;
            }

            $recipients[] = [
                'name' =>
                    $name,

                'email' =>
                    $email,

                'reference' =>
                    $reference,
            ];
        }

        return $recipients;
    }

    /**
     * @return array<int, array{name: string, email: string, reference: string}>
     */
    private function csvRecipients(
        ?UploadedFile $file
    ): array {
        if ($file === null) {
            return [];
        }

        $extension = Str::lower(
            $file->getClientOriginalExtension()
        );

        if (
            ! in_array(
                $extension,
                [
                    'csv',
                    'txt',
                ],
                true
            )
        ) {
            throw ValidationException::
                withMessages([
                    'recipient_file' =>
                        'Upload a CSV or TXT file.',
                ]);
        }

        $handle = fopen(
            $file->getRealPath(),
            'rb'
        );

        if ($handle === false) {
            throw ValidationException::
                withMessages([
                    'recipient_file' =>
                        'The recipient file could not be read.',
                ]);
        }

        $recipients = [];
        $firstDataRow = true;

        try {
            while (
                (
                    $row = fgetcsv(
                        $handle,
                        0,
                        ',',
                        '"',
                        ''
                    )
                ) !== false
            ) {
                if ($row === [null]) {
                    continue;
                }

                $row = array_map(
                    fn ($value): string =>
                        trim(
                            (string) $value
                        ),
                    $row
                );

                if (isset($row[0])) {
                    $row[0] = preg_replace(
                        '/^\xEF\xBB\xBF/',
                        '',
                        $row[0]
                    ) ?? $row[0];
                }

                if (
                    implode('', $row)
                    === ''
                ) {
                    continue;
                }

                if (
                    $firstDataRow
                    && $this->isCsvHeader(
                        $row
                    )
                ) {
                    $firstDataRow = false;

                    continue;
                }

                $firstDataRow = false;

                $recipients[] = [
                    'name' =>
                        $row[0] ?? '',

                    'email' =>
                        $row[1] ?? '',

                    'reference' =>
                        $row[2] ?? '',
                ];
            }
        } finally {
            fclose($handle);
        }

        return $recipients;
    }

    /**
     * @param array<int, string> $row
     */
    private function isCsvHeader(
        array $row
    ): bool {
        $first = Str::of(
            $row[0] ?? ''
        )
            ->lower()
            ->replace(
                [
                    ' ',
                    '-',
                ],
                '_'
            )
            ->toString();

        $second = Str::of(
            $row[1] ?? ''
        )
            ->lower()
            ->replace(
                [
                    ' ',
                    '-',
                ],
                '_'
            )
            ->toString();

        return in_array(
            $first,
            [
                'name',
                'full_name',
                'recipient_name',
                'signer_name',
            ],
            true
        )
            && in_array(
                $second,
                [
                    'email',
                    'email_address',
                    'recipient_email',
                    'signer_email',
                ],
                true
            );
    }

    /**
     * @param array<int, array{name: string, email: string, reference?: string|null}> $recipients
     * @return array<int, array{name: string, email: string, reference: ?string}>
     */
    private function uniqueRecipients(
        array $recipients
    ): array {
        $seenEmails = [];
        $normalized = [];

        foreach ($recipients as $recipient) {
            $email = Str::lower(
                trim(
                    $recipient['email']
                )
            );

            if (isset($seenEmails[$email])) {
                throw ValidationException::
                    withMessages([
                        'recipients' =>
                            "The email address {$email} appears more than once. Each recipient must have a unique email address.",
                    ]);
            }

            $seenEmails[$email] = true;

            $reference = trim(
                (string) (
                    $recipient['reference']
                    ?? ''
                )
            );

            $normalized[] = [
                'name' =>
                    trim(
                        $recipient['name']
                    ),

                'email' =>
                    $email,

                'reference' =>
                    $reference !== ''
                        ? $reference
                        : null,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function expiresAt(
        array $validated
    ): ?CarbonImmutable {
        if (
            blank(
                $validated[
                    'expires_date'
                ]
                ?? null
            )
        ) {
            return null;
        }

        $expiresTime =
            $validated[
                'expires_time'
            ]
            ?? '00:00';

        $expiresAt =
            CarbonImmutable::createFromFormat(
                '!Y-m-d H:i',
                $validated[
                    'expires_date'
                ].' '.$expiresTime,
                config('app.timezone')
            );

        if (! $expiresAt->isAfter(now())) {
            throw ValidationException::
                withMessages([
                    'expires_date' =>
                        'The signing deadline must be in the future.',
                ]);
        }

        return $expiresAt;
    }

    private function ensureTemplateBelongsToOrganization(
        Request $request,
        ConsentTemplate $consentTemplate
    ): void {
        abort_unless(
            (int) $consentTemplate
                ->organization_id
            === (int) $request
                ->user()
                ->organization_id,
            403
        );
    }

    private function ensureCampaignBelongsToOrganization(
        Request $request,
        ConsentCampaign $consentCampaign
    ): void {
        abort_unless(
            (int) $consentCampaign
                ->organization_id
            === (int) $request
                ->user()
                ->organization_id,
            403
        );
    }
}
