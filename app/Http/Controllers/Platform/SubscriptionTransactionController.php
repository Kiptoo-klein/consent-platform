<?php

namespace App\Http\Controllers\Platform;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionTransactionStatus;
use App\Enums\SubscriptionTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionTransaction;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
                'plan',
                'recordedBy',
            ])
            ->latest('id')
            ->paginate(20);

        return view(
            'platform.subscription-transactions.index',
            [
                'organization' => $organization,
                'subscription' => $subscription,
                'transactions' => $transactions,

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

        DB::transaction(function () use (
            $request,
            $organization,
            $validated,
            $type,
            $status,
            $paidAt,
            $periodStart,
            $periodEnd
        ): void {
            $subscription = $organization
                ->subscription()
                ->lockForUpdate()
                ->firstOrFail();

            $oldSubscription =
                $this->subscriptionSnapshot(
                    $subscription
                );

            $transaction =
                SubscriptionTransaction::query()->create([
                    'organization_subscription_id' =>
                        $subscription->id,

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
                        strtoupper(
                            $validated['currency']
                        ),

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
