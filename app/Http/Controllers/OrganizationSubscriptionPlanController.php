<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Enums\SubscriptionInvoiceStatus;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SigningStation;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SubscriptionUsageLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class OrganizationSubscriptionPlanController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly SubscriptionUsageLimitService $usageLimitService
    ) {
    }

    /**
     * Display current and available subscription plans.
     */
    public function index(
        Request $request
    ): View {
        $user = $request->user();

        $organization = $user
            ->organization()
            ->with([
                'subscription.plan',
                'subscription.billingOwner',
            ])
            ->firstOrFail();

        $subscription =
            $organization->subscription;

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $pendingRequest =
            OrganizationSubscriptionPlanRequest::query()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->where(
                    'status',
                    OrganizationSubscriptionPlanRequest::
                        STATUS_PENDING
                )
                ->with([
                    'requestedPlan',
                    'invoice',
                    'requestedBy',
                ])
                ->latest('id')
                ->first();

        $canRequestPlan =
            $subscription !== null
            && $this->canRequestPlan(
                $user,
                $subscription
            );

        abort_unless(
            $canRequestPlan,
            403,
            'Only the Organization Admin or current Billing Owner may access subscription plans.'
        );

        return view(
            'organization-subscription.plans',
            [
                'organization' =>
                    $organization,

                'subscription' =>
                    $subscription,

                'plans' =>
                    $plans,

                'usage' =>
                    $this->usageFor(
                        $organization
                    ),

                'pendingRequest' =>
                    $pendingRequest,

                'canRequestPlan' =>
                    $canRequestPlan,
            ]
        );
    }

    /**
     * Submit a monthly or annual plan request.
     */
    public function store(
        Request $request,
        SubscriptionPlan $subscriptionPlan
    ): RedirectResponse {
        $validated = $request->validate([
            'billing_cycle' => [
                'required',
                'string',
                Rule::in([
                    OrganizationSubscriptionPlanRequest::
                        BILLING_CYCLE_MONTHLY,

                    OrganizationSubscriptionPlanRequest::
                        BILLING_CYCLE_ANNUAL,
                ]),
            ],
        ]);

        $user = $request->user();

        $organization = $user
            ->organization()
            ->with('subscription')
            ->firstOrFail();

        $subscription =
            $organization->subscription;

        abort_if(
            $subscription === null,
            422,
            'No organization subscription is available.'
        );

        abort_unless(
            $this->canRequestPlan(
                $user,
                $subscription
            ),
            403,
            'Only the Organization Admin or Billing Owner may request a plan.'
        );

        abort_unless(
            $subscriptionPlan->is_active,
            422,
            'The selected subscription plan is not available.'
        );

        $billingCycle =
            $validated['billing_cycle'];

        if (
            (int) $subscription
                ->subscription_plan_id
                === (int) $subscriptionPlan->id
            && (string) $subscription
                ->billing_cycle
                === $billingCycle
        ) {
            throw ValidationException::withMessages([
                'billing_cycle' =>
                    'This is already the organization’s active plan and billing cycle.',
            ]);
        }

        $amount =
            $this->amountForCycle(
                $subscriptionPlan,
                $billingCycle
            );

        $usage =
            $this->usageFor(
                $organization
            );

        $limitErrors =
            $this->planLimitErrors(
                $subscriptionPlan,
                $usage
            );

        if ($limitErrors !== []) {
            throw ValidationException::withMessages([
                'subscription_plan' =>
                    implode(
                        ' ',
                        $limitErrors
                    ),
            ]);
        }

        $result = DB::transaction(
            function () use (
                $organization,
                $subscription,
                $subscriptionPlan,
                $user,
                $billingCycle,
                $amount
            ): array {
                $lockedSubscription =
                    OrganizationSubscription::query()
                        ->whereKey(
                            $subscription->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $lockedPlan =
                    SubscriptionPlan::query()
                        ->whereKey(
                            $subscriptionPlan->id
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $lockedAmount =
                    $this->amountForCycle(
                        $lockedPlan,
                        $billingCycle
                    );

                $existingRequest =
                    OrganizationSubscriptionPlanRequest::
                        query()
                        ->where(
                            'organization_id',
                            $organization->id
                        )
                        ->where(
                            'status',
                            OrganizationSubscriptionPlanRequest::
                                STATUS_PENDING
                        )
                        ->with('invoice')
                        ->lockForUpdate()
                        ->latest('id')
                        ->first();

                if ($existingRequest !== null) {
                    if (
                        (int) $existingRequest
                            ->requested_subscription_plan_id
                            === (int) $lockedPlan->id
                        && $existingRequest
                            ->billing_cycle
                            === $billingCycle
                    ) {
                        return [
                            'request' =>
                                $existingRequest,

                            'created' =>
                                false,
                        ];
                    }

                    throw ValidationException::withMessages([
                        'subscription_plan' =>
                            'A different subscription plan request is already pending. It must be resolved before another plan can be requested.',
                    ]);
                }

                $invoice =
                    SubscriptionInvoice::query()
                        ->forceCreate([
                            'organization_subscription_id' =>
                                $lockedSubscription->id,

                            'organization_id' =>
                                $organization->id,

                            'subscription_plan_id' =>
                                $lockedPlan->id,

                            'invoice_number' =>
                                $this->invoiceNumber(
                                    $organization
                                ),

                            'status' =>
                                SubscriptionInvoiceStatus::
                                    DRAFT->value,

                            'issue_date' => null,
                            'due_date' => null,

                            'subtotal' =>
                                $lockedAmount,

                            'tax_amount' =>
                                '0.00',

                            'total_amount' =>
                                $lockedAmount,

                            'currency' =>
                                strtoupper(
                                    (string) (
                                        $lockedPlan->currency
                                        ?: 'KES'
                                    )
                                ),

                            'notes' =>
                                'Automatically created from an organization subscription plan request. Billing cycle: '
                                .ucfirst(
                                    $billingCycle
                                )
                                .'.',

                            'issued_by_user_id' =>
                                null,

                            'paid_at' => null,
                            'voided_at' => null,
                            'cancelled_at' => null,
                        ]);

                $planRequest =
                    OrganizationSubscriptionPlanRequest::
                        query()
                        ->create([
                            'organization_id' =>
                                $organization->id,

                            'organization_subscription_id' =>
                                $lockedSubscription->id,

                            'current_subscription_plan_id' =>
                                $lockedSubscription
                                    ->subscription_plan_id,

                            'requested_subscription_plan_id' =>
                                $lockedPlan->id,

                            'requested_by_user_id' =>
                                $user->id,

                            'subscription_invoice_id' =>
                                $invoice->id,

                            'billing_cycle' =>
                                $billingCycle,

                            'status' =>
                                OrganizationSubscriptionPlanRequest::
                                    STATUS_PENDING,

                            'monthly_price_snapshot' =>
                                $lockedPlan
                                    ->monthly_price,

                            'annual_discount_percent_snapshot' =>
                                $lockedPlan
                                    ->annual_discount_percent
                                    ?? '0.00',

                            'amount_snapshot' =>
                                $lockedAmount,

                            'currency' =>
                                strtoupper(
                                    (string) (
                                        $lockedPlan->currency
                                        ?: 'KES'
                                    )
                                ),

                            'requested_at' =>
                                now(),

                            'resolved_at' =>
                                null,

                            'resolved_by_user_id' =>
                                null,
                        ]);

                $this->activityLogger->log(
                    action:
                        'organization.subscription_plan_requested',

                    description:
                        'An organization subscription plan change was requested.',

                    subject:
                        $planRequest,

                    organizationId:
                        $organization->id,

                    properties: [
                        'current_subscription_plan_id' =>
                            $lockedSubscription
                                ->subscription_plan_id,

                        'requested_subscription_plan_id' =>
                            $lockedPlan->id,

                        'requested_plan_name' =>
                            $lockedPlan->name,

                        'billing_cycle' =>
                            $billingCycle,

                        'amount' =>
                            $lockedAmount,

                        'currency' =>
                            strtoupper(
                                (string) (
                                    $lockedPlan->currency
                                    ?: 'KES'
                                )
                            ),

                        'subscription_invoice_id' =>
                            $invoice->id,

                        'invoice_number' =>
                            $invoice->invoice_number,

                        'requested_by_user_id' =>
                            $user->id,
                    ],
                );

                return [
                    'request' =>
                        $planRequest,

                    'created' =>
                        true,
                ];
            },
            3
        );

        return redirect()
            ->route(
                'organization-subscription-plans.index'
            )
            ->with(
                'success',
                $result['created']
                    ? 'Subscription plan request submitted. A draft invoice has been prepared for Platform Billing review.'
                    : 'This subscription plan request is already pending.'
            );
    }

    /**
     * Cancel an unresolved organization plan request.
     */
    public function cancel(
        Request $request,
        OrganizationSubscriptionPlanRequest $planRequest
    ): RedirectResponse {
        $user = $request->user();

        $organization = $user
            ->organization()
            ->with('subscription')
            ->firstOrFail();

        $subscription =
            $organization->subscription;

        abort_if(
            $subscription === null,
            422,
            'No organization subscription is available.'
        );

        abort_unless(
            $this->canRequestPlan(
                $user,
                $subscription
            ),
            403,
            'Only the Organization Admin or Billing Owner may cancel a plan request.'
        );

        abort_unless(
            (int) $planRequest->organization_id
                === (int) $organization->id,
            404
        );

        DB::transaction(
            function () use (
                $planRequest,
                $organization,
                $user
            ): void {
                $lockedRequest =
                    OrganizationSubscriptionPlanRequest::
                        query()
                        ->whereKey(
                            $planRequest->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                abort_unless(
                    (int) $lockedRequest
                        ->organization_id
                        === (int) $organization->id,
                    404
                );

                if (
                    ! $lockedRequest
                        ->canBeCancelledByOrganization()
                ) {
                    throw ValidationException::
                        withMessages([
                            'plan_request' =>
                                'This plan request can no longer be cancelled directly.',
                        ]);
                }

                $invoice = null;

                if (
                    $lockedRequest
                        ->subscription_invoice_id
                    !== null
                ) {
                    $invoice =
                        SubscriptionInvoice::query()
                            ->whereKey(
                                $lockedRequest
                                    ->subscription_invoice_id
                            )
                            ->lockForUpdate()
                            ->first();
                }

                if (
                    $invoice !== null
                    && $invoice->status
                        !== SubscriptionInvoiceStatus::
                            DRAFT
                ) {
                    throw ValidationException::
                        withMessages([
                            'plan_request' =>
                                'The invoice has already been issued. Contact Platform Billing to request cancellation.',
                        ]);
                }

                $cancelledAt = now();

                $previousRequestStatus =
                    $lockedRequest->status;

                $previousInvoiceStatus =
                    $invoice?->status;

                $lockedRequest->update([
                    'status' =>
                        OrganizationSubscriptionPlanRequest::
                            STATUS_CANCELLED,

                    'resolved_at' =>
                        $cancelledAt,

                    'resolved_by_user_id' =>
                        $user->id,
                ]);

                if ($invoice !== null) {
                    $invoice->update([
                        'status' =>
                            SubscriptionInvoiceStatus::
                                CANCELLED,

                        'cancelled_at' =>
                            $cancelledAt,
                    ]);
                }

                $this->activityLogger->log(
                    action:
                        'organization.subscription_plan_request_cancelled',

                    description:
                        'An organization subscription plan request was cancelled.',

                    subject:
                        $lockedRequest,

                    organizationId:
                        $organization->id,

                    properties: [
                        'requested_subscription_plan_id' =>
                            $lockedRequest
                                ->requested_subscription_plan_id,

                        'billing_cycle' =>
                            $lockedRequest
                                ->billing_cycle,

                        'amount' =>
                            $lockedRequest
                                ->amount_snapshot,

                        'currency' =>
                            $lockedRequest
                                ->currency,

                        'subscription_invoice_id' =>
                            $invoice?->id,

                        'invoice_number' =>
                            $invoice?->invoice_number,

                        'cancelled_by_user_id' =>
                            $user->id,

                        'cancelled_at' =>
                            $cancelledAt
                                ->toIso8601String(),

                        'old' => [
                            'request_status' =>
                                $previousRequestStatus,

                            'invoice_status' =>
                                $previousInvoiceStatus
                                    ?->value,
                        ],

                        'new' => [
                            'request_status' =>
                                OrganizationSubscriptionPlanRequest::
                                    STATUS_CANCELLED,

                            'invoice_status' =>
                                $invoice !== null
                                    ? SubscriptionInvoiceStatus::
                                        CANCELLED->value
                                    : null,
                        ],
                    ],
                );
            },
            3
        );

        return redirect()
            ->route(
                'organization-subscription-plans.index'
            )
            ->with(
                'success',
                'The subscription plan request was cancelled. You may now choose another plan.'
            );
    }

    /**
     * Determine whether the current user may request a plan.
     */
    private function canRequestPlan(
        User $user,
        OrganizationSubscription $subscription
    ): bool {
        app(
            PermissionRegistrar::class
        )->setPermissionsTeamId(
            $user->organization_id
        );

        return $user->hasRole(
            OrganizationRole::
                ORGANIZATION_ADMINISTRATOR
                ->label()
        )
            || (
                $subscription
                    ->billing_owner_user_id
                    !== null
                && (int) $subscription
                    ->billing_owner_user_id
                    === (int) $user->id
            );
    }

    /**
     * Calculate the requested monthly or annual amount.
     */
    private function amountForCycle(
        SubscriptionPlan $plan,
        string $billingCycle
    ): string {
        if ($plan->monthly_price === null) {
            throw ValidationException::withMessages([
                'subscription_plan' =>
                    'Pricing has not been configured for this plan.',
            ]);
        }

        if (
            $billingCycle
            === OrganizationSubscriptionPlanRequest::
                BILLING_CYCLE_ANNUAL
        ) {
            if (! $plan->annual_billing_enabled) {
                throw ValidationException::withMessages([
                    'billing_cycle' =>
                        'Annual billing is not available for this plan.',
                ]);
            }

            $annualPrice =
                $plan->annualPrice();

            if ($annualPrice === null) {
                throw ValidationException::withMessages([
                    'billing_cycle' =>
                        'The annual price could not be calculated.',
                ]);
            }

            return number_format(
                (float) $annualPrice,
                2,
                '.',
                ''
            );
        }

        return number_format(
            (float) $plan->monthly_price,
            2,
            '.',
            ''
        );
    }

    /**
     * Return current organization usage.
     *
     * @return array<string, int>
     */
    private function usageFor(
        Organization $organization
    ): array {
        app(
            PermissionRegistrar::class
        )->setPermissionsTeamId(
            $organization->id
        );

        return [
            'users' =>
                $organization
                    ->users()
                    ->count(),

            'consent_managers' =>
                User::query()
                    ->where(
                        'organization_id',
                        $organization->id
                    )
                    ->role(
                        OrganizationRole::
                            DOCUMENT_MANAGER
                            ->label()
                    )
                    ->count(),

            'staff' =>
                User::query()
                    ->where(
                        'organization_id',
                        $organization->id
                    )
                    ->role(
                        OrganizationRole::
                            WORKFLOW_OPERATOR
                            ->label()
                    )
                    ->count(),

            'auditors' =>
                User::query()
                    ->where(
                        'organization_id',
                        $organization->id
                    )
                    ->role(
                        OrganizationRole::
                            REVIEWER
                            ->label()
                    )
                    ->count(),

            'active_kiosks' =>
                SigningStation::query()
                    ->where(
                        'organization_id',
                        $organization->id
                    )
                    ->where(
                        'active',
                        true
                    )
                    ->count(),

            'consent_templates' =>
                $this
                    ->usageLimitService
                    ->templateUsage(
                        $organization->id
                    ),

            'signed_consents' =>
                $this
                    ->usageLimitService
                    ->signedConsentUsage(
                        $organization->id
                    ),
        ];
    }

    /**
     * Return limit violations for the requested plan.
     *
     * @param array<string, int> $usage
     *
     * @return list<string>
     */
    private function planLimitErrors(
        SubscriptionPlan $plan,
        array $usage
    ): array {
        $checks = [
            [
                'usage' =>
                    $usage['users'],

                'limit' =>
                    $plan->max_users,

                'label' =>
                    'total users',
            ],
            [
                'usage' =>
                    $usage['consent_managers'],

                'limit' =>
                    $plan
                        ->max_consent_managers,

                'label' =>
                    'Consent Managers',
            ],
            [
                'usage' =>
                    $usage['staff'],

                'limit' =>
                    $plan->max_staff,

                'label' =>
                    'Staff users',
            ],
            [
                'usage' =>
                    $usage['auditors'],

                'limit' =>
                    $plan->max_auditors,

                'label' =>
                    'Auditors',
            ],
            [
                'usage' =>
                    $usage['active_kiosks'],

                'limit' =>
                    $plan
                        ->max_active_kiosks,

                'label' =>
                    'active kiosks',
            ],
            [
                'usage' =>
                    $usage['consent_templates'],

                'limit' =>
                    $plan
                        ->max_consent_templates,

                'label' =>
                    'consent templates',
            ],
            [
                'usage' =>
                    $usage['signed_consents'],

                'limit' =>
                    $plan
                        ->max_signed_consents_per_period,

                'label' =>
                    'signed consents in the current billing period',
            ],
        ];

        $errors = [];

        foreach ($checks as $check) {
            /*
             * A null consent-usage limit means unlimited.
             */
            if ($check['limit'] === null) {
                continue;
            }

            if (
                (int) $check['usage']
                > (int) $check['limit']
            ) {
                $errors[] =
                    "Current {$check['label']} usage is "
                    ."{$check['usage']}, but the selected plan allows "
                    ."{$check['limit']}.";
            }
        }

        return $errors;
    }

    /**
     * Generate a unique subscription invoice number.
     */
    private function invoiceNumber(
        Organization $organization
    ): string {
        do {
            $invoiceNumber =
                'PLAN-'
                .now()->format('YmdHis')
                .'-'
                .$organization->id
                .'-'
                .Str::upper(
                    Str::random(6)
                );
        } while (
            SubscriptionInvoice::query()
                ->where(
                    'invoice_number',
                    $invoiceNumber
                )
                ->exists()
        );

        return $invoiceNumber;
    }
}
