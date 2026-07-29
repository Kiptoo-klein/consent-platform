<?php

namespace App\Http\Controllers\Platform;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionTransactionStatus;
use App\Enums\SubscriptionTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionTransaction;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionTransactionController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    /**
     * Display an organization's subscription transaction history.
     */
    public function index(
        Organization $organization
    ): View {
        $subscription = $organization
            ->subscription()
            ->with('plan')
            ->firstOrFail();

        $transactions = SubscriptionTransaction::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->with([
                'invoice',
                'plan',
                'recordedBy',
            ])
            ->latest('id')
            ->paginate(20);

        $outstandingInvoices =
            SubscriptionInvoice::query()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->where(
                    'organization_subscription_id',
                    $subscription->id
                )
                ->whereIn(
                    'status',
                    [
                        SubscriptionInvoiceStatus::ISSUED->value,
                        SubscriptionInvoiceStatus::OVERDUE->value,
                    ]
                )
                ->orderBy('due_date')
                ->orderBy('invoice_number')
                ->get();

        return view(
            'platform.subscription-transactions.index',
            [
                'organization' => $organization,
                'subscription' => $subscription,
                'transactions' => $transactions,

                'outstandingInvoices' =>
                    $outstandingInvoices,

                'transactionTypes' =>
                    SubscriptionTransactionType::cases(),

                'transactionStatuses' =>
                    SubscriptionTransactionStatus::cases(),
            ]
        );
    }

    /**
     * Record a payment, renewal, refund, or adjustment.
     */
    public function store(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $successfulRenewal =
            $request->input('type')
                === SubscriptionTransactionType::RENEWAL->value
            && $request->input('status')
                === SubscriptionTransactionStatus::SUCCESSFUL->value;

        $successfulTransaction =
            $request->input('status')
                === SubscriptionTransactionStatus::SUCCESSFUL->value;

        $validated = $request->validate([
            'reference' => [
                'required',
                'string',
                'max:120',
                Rule::unique(
                    'subscription_transactions',
                    'reference'
                ),
            ],

            'subscription_invoice_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'subscription_invoices',
                    'id'
                ),
            ],

            'type' => [
                'required',
                Rule::in(
                    array_column(
                        SubscriptionTransactionType::cases(),
                        'value'
                    )
                ),
            ],

            'status' => [
                'required',
                Rule::in(
                    array_column(
                        SubscriptionTransactionStatus::cases(),
                        'value'
                    )
                ),
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'currency' => [
                'required',
                'string',
                'regex:/^[A-Za-z]{3}$/',
            ],

            'payment_method' => [
                'nullable',
                'string',
                'max:100',
            ],

            'paid_at' => [
                Rule::requiredIf($successfulTransaction),
                'nullable',
                'date_format:Y-m-d\TH:i',
            ],

            'period_starts_at' => [
                Rule::requiredIf($successfulRenewal),
                'nullable',
                'date_format:Y-m-d\TH:i',
            ],

            'period_ends_at' => [
                Rule::requiredIf($successfulRenewal),
                'nullable',
                'date_format:Y-m-d\TH:i',
                'after:period_starts_at',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $type = SubscriptionTransactionType::from(
            $validated['type']
        );

        $status = SubscriptionTransactionStatus::from(
            $validated['status']
        );

        $paidAt = filled($validated['paid_at'] ?? null)
            ? Carbon::createFromFormat(
                'Y-m-d\TH:i',
                $validated['paid_at']
            )
            : null;

        $periodStart =
            filled($validated['period_starts_at'] ?? null)
                ? Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    $validated['period_starts_at']
                )
                : null;

        $periodEnd =
            filled($validated['period_ends_at'] ?? null)
                ? Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    $validated['period_ends_at']
                )
                : null;

        $invoiceId =
            filled(
                $validated['subscription_invoice_id']
                    ?? null
            )
                ? (int) $validated['subscription_invoice_id']
                : null;

        $currency = strtoupper(
            $validated['currency']
        );

        DB::transaction(function () use (
            $request,
            $organization,
            $validated,
            $type,
            $status,
            $paidAt,
            $periodStart,
            $periodEnd,
            $invoiceId,
            $currency
        ): void {
            $subscription = $organization
                ->subscription()
                ->lockForUpdate()
                ->firstOrFail();

            $invoice = null;

            if ($invoiceId !== null) {
                $invoice = SubscriptionInvoice::query()
                    ->whereKey($invoiceId)
                    ->lockForUpdate()
                    ->first();

                $this->ensureInvoiceCanReceiveTransaction(
                    $invoice,
                    $organization,
                    $subscription,
                    $currency
                );
            }

            $oldSubscription =
                $this->subscriptionSnapshot(
                    $subscription
                );

            $transaction =
                SubscriptionTransaction::query()->create([
                    'organization_subscription_id' =>
                        $subscription->id,

                    'subscription_invoice_id' =>
                        $invoice?->id,

                    'organization_id' =>
                        $organization->id,

                    'subscription_plan_id' =>
                        $subscription->subscription_plan_id,

                    'reference' =>
                        trim($validated['reference']),

                    'type' =>
                        $type,

                    'status' =>
                        $status,

                    'amount' =>
                        $validated['amount'],

                    'currency' =>
                        $currency,

                    'payment_method' =>
                        filled(
                            $validated['payment_method']
                                ?? null
                        )
                            ? trim(
                                $validated['payment_method']
                            )
                            : null,

                    'paid_at' =>
                        $paidAt,

                    'period_starts_at' =>
                        $periodStart,

                    'period_ends_at' =>
                        $periodEnd,

                    'notes' =>
                        filled($validated['notes'] ?? null)
                            ? trim($validated['notes'])
                            : null,

                    'recorded_by_user_id' =>
                        $request->user()->id,
                ]);

            if (
                $status
                    === SubscriptionTransactionStatus::SUCCESSFUL
                && $type
                    === SubscriptionTransactionType::PAYMENT
            ) {
                $subscription->update([
                    'payment_status' =>
                        SubscriptionPaymentStatus::PAID,
                ]);
            }

            if (
                $status
                    === SubscriptionTransactionStatus::SUCCESSFUL
                && $type
                    === SubscriptionTransactionType::RENEWAL
            ) {
                $subscription->update([
                    'status' =>
                        OrganizationSubscriptionStatus::ACTIVE,

                    'payment_status' =>
                        SubscriptionPaymentStatus::PAID,

                    'trial_ends_at' =>
                        null,

                    'current_period_starts_at' =>
                        $periodStart,

                    'current_period_ends_at' =>
                        $periodEnd,

                    'cancelled_at' =>
                        null,

                    'ends_at' =>
                        null,
                ]);
            }

            $subscription->refresh();
            $transaction->refresh();

            if (
                $invoice !== null
                && $status
                    === SubscriptionTransactionStatus::SUCCESSFUL
                && in_array(
                    $type,
                    [
                        SubscriptionTransactionType::PAYMENT,
                        SubscriptionTransactionType::RENEWAL,
                    ],
                    true
                )
                && $invoice->status?->isOutstanding()
            ) {
                $paidTotal = $invoice
                    ->transactions()
                    ->where(
                        'status',
                        SubscriptionTransactionStatus::SUCCESSFUL
                            ->value
                    )
                    ->whereIn(
                        'type',
                        [
                            SubscriptionTransactionType::PAYMENT
                                ->value,

                            SubscriptionTransactionType::RENEWAL
                                ->value,
                        ]
                    )
                    ->sum('amount');

                if (
                    round((float) $paidTotal, 2)
                        >= round(
                            (float) $invoice->total_amount,
                            2
                        )
                ) {
                    $invoice->update([
                        'status' =>
                            SubscriptionInvoiceStatus::PAID,

                        'paid_at' =>
                            $paidAt ?? now(),
                    ]);

                    $invoice->refresh();

                    $this->activityLogger->log(
                        action:
                            'organization.subscription_invoice_paid',

                        description:
                            'Subscription invoice marked as paid.',

                        subject:
                            $invoice,

                        organizationId:
                            $organization->id,

                        properties: [
                            'invoice_number' =>
                                $invoice->invoice_number,

                            'paid_total' =>
                                number_format(
                                    (float) $paidTotal,
                                    2,
                                    '.',
                                    ''
                                ),

                            'total_amount' =>
                                $invoice->total_amount,

                            'currency' =>
                                $invoice->currency,

                            'paid_at' =>
                                $invoice->paid_at
                                    ?->toIso8601String(),

                            'transaction_reference' =>
                                $transaction->reference,
                        ],
                    );
                }
            }

            $this->activityLogger->log(
                action:
                    'organization.subscription_transaction_recorded',

                description:
                    'Subscription transaction recorded.',

                subject:
                    $transaction,

                organizationId:
                    $organization->id,

                properties: [
                    'reference' =>
                        $transaction->reference,

                    'subscription_invoice_id' =>
                        $transaction->subscription_invoice_id,

                    'invoice_number' =>
                        $invoice?->invoice_number,

                    'type' =>
                        $transaction->type?->value,

                    'status' =>
                        $transaction->status?->value,

                    'amount' =>
                        $transaction->amount,

                    'currency' =>
                        $transaction->currency,

                    'payment_method' =>
                        $transaction->payment_method,

                    'paid_at' =>
                        $transaction->paid_at
                            ?->toIso8601String(),

                    'period_starts_at' =>
                        $transaction->period_starts_at
                            ?->toIso8601String(),

                    'period_ends_at' =>
                        $transaction->period_ends_at
                            ?->toIso8601String(),

                    'subscription' => [
                        'old' =>
                            $oldSubscription,

                        'new' =>
                            $this->subscriptionSnapshot(
                                $subscription
                            ),
                    ],
                ],
            );
        });

        return redirect()
            ->route(
                'platform.organizations.subscription-transactions.index',
                $organization
            )
            ->with(
                'success',
                'Subscription transaction recorded successfully.'
            );
    }

    /**
     * Display a single subscription transaction receipt.
     */
    public function show(
        Organization $organization,
        SubscriptionTransaction $subscriptionTransaction
    ): View {
        abort_unless(
            $subscriptionTransaction->organization_id
                === $organization->id,
            404
        );

        $subscriptionTransaction->load([
            'organization',
            'subscription',
            'plan',
            'recordedBy',
        ]);

        return view(
            'platform.subscription-transactions.show',
            [
                'organization' =>
                    $organization,

                'transaction' =>
                    $subscriptionTransaction,
            ]
        );
    }

    /**
     * Validate a selected invoice before linking a transaction.
     */
    private function ensureInvoiceCanReceiveTransaction(
        ?SubscriptionInvoice $invoice,
        Organization $organization,
        OrganizationSubscription $subscription,
        string $currency
    ): void {
        $valid = $invoice !== null
            && (int) $invoice->organization_id
                === (int) $organization->id
            && (int) $invoice->organization_subscription_id
                === (int) $subscription->id
            && $invoice->status?->isOutstanding()
            && strtoupper($invoice->currency)
                === $currency;

        if (! $valid) {
            throw ValidationException::withMessages([
                'subscription_invoice_id' =>
                    'The selected invoice is not eligible for this transaction.',
            ]);
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function subscriptionSnapshot(
        OrganizationSubscription $subscription
    ): array {
        return [
            'status' =>
                $subscription->status?->value,

            'payment_status' =>
                $subscription->payment_status?->value,

            'trial_ends_at' =>
                $subscription->trial_ends_at
                    ?->toIso8601String(),

            'current_period_starts_at' =>
                $subscription->current_period_starts_at
                    ?->toIso8601String(),

            'current_period_ends_at' =>
                $subscription->current_period_ends_at
                    ?->toIso8601String(),

            'cancelled_at' =>
                $subscription->cancelled_at
                    ?->toIso8601String(),

            'ends_at' =>
                $subscription->ends_at
                    ?->toIso8601String(),
        ];
    }
}
