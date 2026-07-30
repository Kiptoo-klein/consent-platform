<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionInvoiceStatus;
use App\Models\Organization;
use App\Models\OrganizationInvoiceReminderPreference;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionTransaction;
use App\Services\ActivityLogger;
use App\Services\OrganizationInvoiceReminderPreferenceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            ]
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
                'An Organization Admin viewed a subscription receipt.',

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
                'An Organization Admin downloaded a subscription receipt.',

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
                'An Organization Admin viewed a subscription invoice.',

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
                'An Organization Admin downloaded a subscription invoice.',

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
     * Resolve and authorize the current organization's billing owner.
     *
     * @return array{
     *     0: Organization,
     *     1: OrganizationSubscription
     * }
     */
    private function billingContext(
        Request $request
    ): array {
        $user = $request->user();

        $organization = $user
            ->organization()
            ->with([
                'subscription.plan',
            ])
            ->firstOrFail();

        $subscription = $organization->subscription;

        abort_unless(
            $subscription !== null
            && (int) $subscription->billing_owner_user_id
                === (int) $user->id,
            403
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
