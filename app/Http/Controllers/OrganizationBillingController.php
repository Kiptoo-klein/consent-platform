<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Enums\SubscriptionInvoiceStatus;
use App\Models\Organization;
use App\Models\OrganizationInvoiceReminderPreference;
use App\Models\OrganizationInvoiceReminderRecipient;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\OrganizationInvoiceReminderPreferenceService;
use App\Services\OrganizationInvoiceReminderRecipientService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class OrganizationBillingController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    /**
     * Display read-only subscription billing history.
     */
    public function index(Request $request): View
    {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);


        $subscription->loadMissing([
            'billingOwner.roles',
        ]);

        $billingOwner =
            $subscription->billingOwner;

        $billingUsers = $organization
            ->users()
            ->where('is_active', true)
            ->with('roles')
            ->orderByRaw(
                'CASE WHEN users.id = ? THEN 0 ELSE 1 END',
                [
                    (int) $subscription
                        ->billing_owner_user_id,
                ]
            )
            ->orderBy('users.name')
            ->orderBy('users.email')
            ->get([
                'users.id',
                'users.name',
                'users.email',
            ]);

        $isOrganizationAdministrator =
            $this->isOrganizationAdministrator(
                $request->user(),
                $organization
            );

        $transactions = SubscriptionTransaction::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->where(
                'organization_subscription_id',
                $subscription->id
            )
            ->with([
                'plan',
                'recordedBy',
            ])
            ->latest('id')
            ->paginate(20);

        $invoices = SubscriptionInvoice::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->where(
                'organization_subscription_id',
                $subscription->id
            )
            ->where(
                'status',
                '!=',
                SubscriptionInvoiceStatus::DRAFT->value
            )
            ->with([
                'plan',
                'issuedBy',
            ])
            ->latest('id')
            ->paginate(
                20,
                ['*'],
                'invoice_page'
            );

        return view(
            'organization-billing.index',
            [
                'organization' =>
                    $organization,

                'subscription' =>
                    $subscription,

                'transactions' =>
                    $transactions,

                'invoices' =>
                    $invoices,


                'billingOwner' =>
                    $billingOwner,

                'billingUsers' =>
                    $billingUsers,

                'isOrganizationAdministrator' =>
                    $isOrganizationAdministrator,
            ]
        );
    }

    /**
     * Assign the Billing Owner role to one active organization user.
     *
     * This billing assignment is separate from the user's normal
     * organization role.
     */
    public function updateBillingOwner(
        Request $request
    ): RedirectResponse {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        abort_unless(
            $this->isOrganizationAdministrator(
                $request->user(),
                $organization
            ),
            403,
            'Only an Organization Admin can assign the Billing Owner.'
        );

        $validated = $request->validate([
            'billing_owner_user_id' => [
                'required',
                'integer',

                Rule::exists(
                    'users',
                    'id'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'organization_id',
                                $organization->id
                            )
                            ->where('is_active', true)
                            ->whereNull('deleted_at')
                ),
            ],
        ]);

        $newBillingOwner = User::query()
            ->whereKey(
                (int) $validated[
                    'billing_owner_user_id'
                ]
            )
            ->where(
                'organization_id',
                $organization->id
            )
            ->where('is_active', true)
            ->firstOrFail();

        if (
            (int) $subscription->billing_owner_user_id
            === (int) $newBillingOwner->id
        ) {
            return redirect()
                ->route('organization-billing.index')
                ->with(
                    'success',
                    'This user is already the Billing Owner.'
                );
        }

        DB::transaction(function () use (
            $organization,
            $subscription,
            $newBillingOwner
        ): void {
            $lockedSubscription =
                OrganizationSubscription::query()
                    ->whereKey($subscription->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            $previousBillingOwner = User::query()
                ->find(
                    $lockedSubscription
                        ->billing_owner_user_id
                );

            $lockedSubscription->update([
                'billing_owner_user_id' =>
                    $newBillingOwner->id,
            ]);

            $this->activityLogger->log(
                action:
                    'organization.billing_owner_assigned',

                description:
                    "Assigned {$newBillingOwner->name} "
                    .'as the Billing Owner.',

                subject:
                    $lockedSubscription,

                organizationId:
                    $organization->id,

                properties: [
                    'old' => [
                        'billing_owner_user_id' =>
                            $previousBillingOwner?->id,

                        'billing_owner_name' =>
                            $previousBillingOwner?->name,

                        'billing_owner_email' =>
                            $previousBillingOwner?->email,
                    ],

                    'new' => [
                        'billing_owner_user_id' =>
                            $newBillingOwner->id,

                        'billing_owner_name' =>
                            $newBillingOwner->name,

                        'billing_owner_email' =>
                            $newBillingOwner->email,
                    ],
                ],
            );
        });

        return redirect()
            ->route('organization-billing.index')
            ->with(
                'success',
                "{$newBillingOwner->name} is now the Billing Owner."
            );
    }

    /**
     * Display an HTML receipt belonging to the billing owner's organization.
     */
    public function show(
        Request $request,
        SubscriptionTransaction $subscriptionTransaction
    ): View {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        $this->ensureTransactionBelongsToSubscription(
            $subscriptionTransaction,
            $organization,
            $subscription
        );

        $subscriptionTransaction->load([
            'organization',
            'subscription',
            'plan',
            'recordedBy',
        ]);

        $this->activityLogger->log(
            action:
                'organization.subscription_receipt_viewed',

            description:
                'An authorized billing user viewed a subscription receipt.',

            subject:
                $subscriptionTransaction,

            organizationId:
                $organization->id,

            properties: [
                'reference' =>
                    $subscriptionTransaction->reference,

                'amount' =>
                    $subscriptionTransaction->amount,

                'currency' =>
                    $subscriptionTransaction->currency,
            ],
        );

        return view(
            'organization-billing.show',
            [
                'organization' =>
                    $organization,

                'transaction' =>
                    $subscriptionTransaction,
            ]
        );
    }

    /**
     * Generate and download a subscription receipt as a PDF.
     */
    public function download(
        Request $request,
        SubscriptionTransaction $subscriptionTransaction
    ): Response {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        $this->ensureTransactionBelongsToSubscription(
            $subscriptionTransaction,
            $organization,
            $subscription
        );

        $subscriptionTransaction->load([
            'organization',
            'subscription',
            'plan',
            'recordedBy',
        ]);

        $filename = $this->downloadFilename(
            $subscriptionTransaction
        );

        $this->activityLogger->log(
            action:
                'organization.subscription_receipt_downloaded',

            description:
                'An authorized billing user downloaded a subscription receipt.',

            subject:
                $subscriptionTransaction,

            organizationId:
                $organization->id,

            properties: [
                'reference' =>
                    $subscriptionTransaction->reference,

                'amount' =>
                    $subscriptionTransaction->amount,

                'currency' =>
                    $subscriptionTransaction->currency,

                'download_filename' =>
                    $filename,
            ],
        );

        $pdf = Pdf::loadView(
            'pdfs.subscription-receipt',
            [
                'transaction' =>
                    $subscriptionTransaction,
            ]
        );

        $pdf->setPaper(
            'a4',
            'portrait'
        );

        return $pdf->download($filename);
    }

    /**
     * Display an organization subscription invoice.
     */
    public function showInvoice(
        Request $request,
        SubscriptionInvoice $subscriptionInvoice
    ): View {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        $this->ensureInvoiceBelongsToSubscription(
            $subscriptionInvoice,
            $organization,
            $subscription
        );

        $subscriptionInvoice->load([
            'organization',
            'subscription',
            'plan',
            'issuedBy',

            'transactions' => function ($query): void {
                $query->latest('id');
            },

            'reminderNotifications' => function ($query): void {
                $query
                    ->select([
                        'id',
                        'subscription_invoice_id',
                        'reminder_key',
                        'status',
                        'recipient_email',
                        'created_at',
                    ])
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(20);
            },
        ]);

        $this->activityLogger->log(
            action:
                'organization.subscription_invoice_viewed',

            description:
                'An authorized billing user viewed a subscription invoice.',

            subject:
                $subscriptionInvoice,

            organizationId:
                $organization->id,

            properties: [
                'invoice_number' =>
                    $subscriptionInvoice->invoice_number,

                'status' =>
                    $subscriptionInvoice->status?->value,

                'total_amount' =>
                    $subscriptionInvoice->total_amount,

                'currency' =>
                    $subscriptionInvoice->currency,
            ],
        );

        return view(
            'organization-billing.invoice',
            [
                'organization' =>
                    $organization,

                'invoice' =>
                    $subscriptionInvoice,
            ]
        );
    }

    /**
     * Download an organization subscription invoice.
     */
    public function downloadInvoice(
        Request $request,
        SubscriptionInvoice $subscriptionInvoice
    ): Response {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        $this->ensureInvoiceBelongsToSubscription(
            $subscriptionInvoice,
            $organization,
            $subscription
        );

        $subscriptionInvoice->load([
            'organization',
            'subscription',
            'plan',
            'issuedBy',
            'planRequest',
            'transactions' => function ($query): void {
                $query->latest('id');
            },
        ]);

        $filename = $this->invoiceDownloadFilename(
            $subscriptionInvoice
        );

        $this->activityLogger->log(
            action:
                'organization.subscription_invoice_downloaded',

            description:
                'An authorized billing user downloaded a subscription invoice.',

            subject:
                $subscriptionInvoice,

            organizationId:
                $organization->id,

            properties: [
                'invoice_number' =>
                    $subscriptionInvoice->invoice_number,

                'status' =>
                    $subscriptionInvoice->status?->value,

                'total_amount' =>
                    $subscriptionInvoice->total_amount,

                'currency' =>
                    $subscriptionInvoice->currency,

                'download_filename' =>
                    $filename,
            ],
        );

        $pdf = Pdf::loadView(
            'pdfs.subscription-invoice',
            [
                'invoice' =>
                    $subscriptionInvoice,
            ]
        );

        $pdf->setPaper(
            'a4',
            'portrait'
        );

        return $pdf->download($filename);
    }

    /**
     * Prevent draft and cross-organization invoice access.
     */
    private function ensureInvoiceBelongsToSubscription(
        SubscriptionInvoice $invoice,
        Organization $organization,
        OrganizationSubscription $subscription
    ): void {
        abort_unless(
            (int) $invoice->organization_id
                === (int) $organization->id
            && (int) $invoice->organization_subscription_id
                === (int) $subscription->id
            && $invoice->status
                !== SubscriptionInvoiceStatus::DRAFT,
            403
        );
    }

    /**
     * Return a safe invoice PDF filename.
     */
    private function invoiceDownloadFilename(
        SubscriptionInvoice $invoice
    ): string {
        $safeNumber = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '_',
            $invoice->invoice_number
        );

        $safeNumber = trim(
            (string) $safeNumber,
            '_'
        );

        if ($safeNumber === '') {
            $safeNumber = 'invoice';
        }

        return Str::limit(
            "subscription_invoice_{$safeNumber}",
            180,
            ''
        ).'.pdf';
    }

    /**
     * Display invoice reminder preferences for the billing owner.
     */
    public function reminderPreferences(
        Request $request,
        OrganizationInvoiceReminderPreferenceService $preferenceService
    ): View {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        return view(
            'organization-billing.reminder-preferences',
            [
                'organization' =>
                    $organization,

                'subscription' =>
                    $subscription,

                'preferences' =>
                    $preferenceService->preferencesFor(
                        $organization->id
                    ),
            ]
        );
    }

    /**
     * Update invoice reminder preferences for the billing owner.
     */
    public function updateReminderPreferences(
        Request $request,
        OrganizationInvoiceReminderPreferenceService $preferenceService
    ): RedirectResponse {
        [
            $organization,
        ] = $this->billingContext($request);

        $request->validate([
            'before_due_reminders_enabled' => [
                'required',
                'boolean',
            ],

            'overdue_reminders_enabled' => [
                'required',
                'boolean',
            ],
        ]);

        $oldPreferences =
            $preferenceService->preferencesFor(
                $organization->id
            );

        $newPreferences = [
            'before_due_reminders_enabled' =>
                $request->boolean(
                    'before_due_reminders_enabled'
                ),

            'overdue_reminders_enabled' =>
                $request->boolean(
                    'overdue_reminders_enabled'
                ),
        ];

        DB::transaction(
            function () use (
                $request,
                $organization,
                $oldPreferences,
                $newPreferences
            ): void {
                $preference =
                    OrganizationInvoiceReminderPreference::query()
                        ->where(
                            'organization_id',
                            $organization->id
                        )
                        ->lockForUpdate()
                        ->first();

                if ($preference === null) {
                    $preference =
                        new OrganizationInvoiceReminderPreference();

                    $preference->organization_id =
                        $organization->id;
                }

                $preference->fill(
                    array_merge(
                        $newPreferences,
                        [
                            'updated_by_user_id' =>
                                $request->user()->id,
                        ]
                    )
                );

                $preference->save();

                $this->activityLogger->log(
                    action:
                        'organization.subscription_invoice_reminder_preferences_updated',

                    description:
                        'The organization invoice reminder preferences were updated.',

                    subject:
                        $preference,

                    organizationId:
                        $organization->id,

                    properties: [
                        'old' =>
                            $oldPreferences,

                        'new' =>
                            $newPreferences,
                    ],
                );
            },
            3
        );

        $preferenceService->forgetCache(
            $organization->id
        );

        return redirect()
            ->route(
                'organization-billing.reminder-preferences.index'
            )
            ->with(
                'success',
                'Invoice reminder preferences updated successfully.'
            );
    }

    /**
     * Display optional invoice reminder recipients for the billing owner.
     */
    public function reminderRecipients(
        Request $request,
        OrganizationInvoiceReminderRecipientService $recipientService
    ): View {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        $eligibleUsers =
            User::query()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'id',
                    '!=',
                    $subscription->billing_owner_user_id
                )
                ->orderBy('name')
                ->orderBy('email')
                ->get()
                ->filter(
                    static fn (User $user): bool =>
                        filter_var(
                            trim(
                                (string) $user->email
                            ),
                            FILTER_VALIDATE_EMAIL
                        ) !== false
                )
                ->values();

        return view(
            'organization-billing.reminder-recipients',
            [
                'organization' =>
                    $organization,

                'subscription' =>
                    $subscription,

                'billingOwner' =>
                    $subscription
                        ->billingOwner()
                        ->firstOrFail(),

                'eligibleUsers' =>
                    $eligibleUsers,

                'configuredRecipientUserIds' =>
                    $recipientService
                        ->configuredRecipientUserIds(
                            $organization->id
                        ),
            ]
        );
    }

    /**
     * Replace optional invoice reminder recipients for the billing owner.
     */
    public function updateReminderRecipients(
        Request $request,
        OrganizationInvoiceReminderRecipientService $recipientService
    ): RedirectResponse {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        $validated = $request->validate([
            'recipient_user_ids' => [
                'nullable',
                'array',
            ],

            'recipient_user_ids.*' => [
                'required',
                'integer',
                'distinct',

                Rule::exists(
                    'users',
                    'id'
                )->where(
                    function ($query) use (
                        $organization,
                        $subscription
                    ): void {
                        $query
                            ->where(
                                'organization_id',
                                $organization->id
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                            ->where(
                                'id',
                                '!=',
                                $subscription
                                    ->billing_owner_user_id
                            );
                    }
                ),
            ],
        ]);

        $oldRecipientUserIds =
            $recipientService
                ->configuredRecipientUserIds(
                    $organization->id
                );

        $newRecipientUserIds =
            collect(
                $validated[
                    'recipient_user_ids'
                ] ?? []
            )
                ->map(
                    static fn ($id): int =>
                        (int) $id
                )
                ->sort()
                ->values()
                ->all();

        DB::transaction(
            function () use (
                $request,
                $organization,
                $oldRecipientUserIds,
                $newRecipientUserIds
            ): void {
                Organization::query()
                    ->whereKey(
                        $organization->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                OrganizationInvoiceReminderRecipient::query()
                    ->where(
                        'organization_id',
                        $organization->id
                    )
                    ->delete();

                foreach (
                    $newRecipientUserIds
                    as $recipientUserId
                ) {
                    OrganizationInvoiceReminderRecipient::query()
                        ->create([
                            'organization_id' =>
                                $organization->id,

                            'user_id' =>
                                $recipientUserId,

                            'created_by_user_id' =>
                                $request->user()->id,
                        ]);
                }

                $this->activityLogger->log(
                    action:
                        'organization.subscription_invoice_reminder_recipients_updated',

                    description:
                        'The organization invoice reminder recipients were updated.',

                    subject:
                        $organization,

                    organizationId:
                        $organization->id,

                    properties: [
                        'old' => [
                            'recipient_user_ids' =>
                                $oldRecipientUserIds,
                        ],

                        'new' => [
                            'recipient_user_ids' =>
                                $newRecipientUserIds,
                        ],
                    ],
                );
            },
            3
        );

        $recipientService->forgetCache(
            $organization->id
        );

        return redirect()
            ->route(
                'organization-billing.reminder-recipients.index'
            )
            ->with(
                'success',
                'Invoice reminder recipients updated successfully.'
            );
    }

    /**
     * Determine whether the user is an Organization Admin.
     */
    private function isOrganizationAdministrator(
        User $user,
        Organization $organization
    ): bool {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(
                $organization->id
            );

        return $user->hasRole(
            OrganizationRole::
                ORGANIZATION_ADMINISTRATOR
                ->label()
        );
    }

    /**
     * Resolve the current organization's billing context.
     *
     * Billing is available to the assigned Billing Owner and to an
     * Organization Admin for oversight and reassignment.
     *
     * @return array{0: Organization, 1: OrganizationSubscription}
     */
    private function billingContext(
        Request $request
    ): array {
        $user = $request->user();

        abort_if(
            $user === null
            || $user->organization_id === null,
            403,
            'An organization account is required.'
        );

        $organization = Organization::query()
            ->findOrFail(
                (int) $user->organization_id
            );

        $subscription = $organization
            ->subscription()
            ->with([
                'plan',
                'billingOwner',
            ])
            ->first();

        abort_if(
            $subscription === null,
            404,
            'This organization has no subscription record.'
        );

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(
                $organization->id
            );

        $isOrganizationAdministrator =
            $this->isOrganizationAdministrator(
                $user,
                $organization
            );

        $isBillingOwner =
            (int) $subscription
                ->billing_owner_user_id
            === (int) $user->id;

        abort_unless(
            $isBillingOwner
            || $isOrganizationAdministrator,
            403,
            'Only the Billing Owner or an Organization Admin '
            .'can access organization billing.'
        );

        return [
            $organization,
            $subscription,
        ];
    }

    /**
     * Prevent receipt access across organization boundaries.
     */
    private function ensureTransactionBelongsToSubscription(
        SubscriptionTransaction $transaction,
        Organization $organization,
        OrganizationSubscription $subscription
    ): void {
        abort_unless(
            (int) $transaction->organization_id
                === (int) $organization->id
            && (int) $transaction->organization_subscription_id
                === (int) $subscription->id,
            403
        );
    }

    /**
     * Return a safe receipt filename based on the transaction reference.
     */
    private function downloadFilename(
        SubscriptionTransaction $transaction
    ): string {
        $safeReference = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '_',
            $transaction->reference
        );

        $safeReference = trim(
            (string) $safeReference,
            '_'
        );

        if ($safeReference === '') {
            $safeReference = 'transaction';
        }

        return Str::limit(
            "subscription_receipt_{$safeReference}",
            180,
            ''
        ).'.pdf';
    }
}
