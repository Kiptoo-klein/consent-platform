<?php

namespace App\Http\Controllers\Platform;
use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SigningStation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

/**
 * Handles platform-level organization administration.
 *
 * These actions belong to the central platform administration area rather
 * than an individual organization's dashboard. Access is protected by the
 * platform role middleware configured in the platform route group.
 */
class OrganizationController extends Controller
{
    /**
     * Create the Platform Organization controller.
     */
    public function __construct(
        protected ActivityLogger $activityLogger
    ) {
    }

    /**
     * Display a paginated list of all organizations.
     */
    public function index(): View
    {
        $organizations = Organization::query()
            ->withCount([
                'users',
                'consentTemplates',
            ])
            ->latest()
            ->paginate(15);

        return view('platform.organizations.index', compact('organizations'));
    }

    /**
     * Display a single organization and its summary statistics.
     */
    public function show(Organization $organization): View
    {
        $organization
            ->load([
                'subscription.plan',
                'subscription.billingOwner',
                'subscription.bypassApprover',
            ])
            ->loadCount([
                'users',
                'consentTemplates',
            ]);

        $subscriptionPlans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($organization->id);

        /*
         * The oldest active Organization Admin is the default billing
         * owner, but a Platform Admin may explicitly select another active
         * organization user.
         */
        $defaultBillingOwner = $organization
            ->users()
            ->where('is_active', true)
            ->whereHas(
                'roles',
                function ($query) use (
                    $organization
                ): void {
                    $query
                        ->where(
                            'roles.organization_id',
                            $organization->id
                        )
                        ->where(
                            'roles.guard_name',
                            'web'
                        )
                        ->where(
                            'roles.name',
                            \App\Enums\OrganizationRole::
                                ORGANIZATION_ADMINISTRATOR
                                ->label()
                        );
                }
            )
            ->orderBy('users.created_at')
            ->orderBy('users.id')
            ->first([
                'users.id',
            ]);

        $defaultInitialBillingOwnerId =
            $defaultBillingOwner?->id;

        $billingOwners = $organization
            ->users()
            ->where('is_active', true)
            ->orderByRaw(
                'CASE WHEN users.id = ? THEN 0 ELSE 1 END',
                [
                    (int) (
                        $defaultInitialBillingOwnerId ?? 0
                    ),
                ]
            )
            ->orderBy('users.name')
            ->orderBy('users.email')
            ->get([
                'users.id',
                'users.name',
                'users.email',
                'users.created_at',
            ]);

        $usage = $this->subscriptionUsage(
            $organization
        );

        $capacity = $this->capacityForPlan(
            $organization->subscription?->plan,
            $usage
        );

        $hasCapacityOverage = collect($capacity)->contains(
            fn (array $item): bool =>
                $item['overage'] > 0
        );

        return view(
            'platform.organizations.show',
            compact(
                'organization',
                'subscriptionPlans',
                'billingOwners',
                'defaultInitialBillingOwnerId',
                'capacity',
                'hasCapacityOverage'
            )
        );
    }

    /**
     * Display the organization edit form.
     */
    public function edit(Organization $organization): View
    {
        return view('platform.organizations.edit', compact('organization'));
    }

    /**
     * Update an organization's profile, contact information, and branding.
     */
    public function update(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('organizations', 'slug')
                    ->ignore($organization->id),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'support_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'footer_text' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'primary_color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'secondary_color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'accent_color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        $organization->update($validated);

        return redirect()
            ->route('platform.organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }

    /**
     * Create the organization's initial subscription record.
     *
     * The initial record is unpaid. An optional future trial end grants
     * temporary trial access. A Platform Admin can later record payment,
     * renew the subscription, change its plan, or approve a bypass.
     */
    public function storeSubscription(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        /*
         * Non-browser callers may omit the start date. Treat that as
         * today's date, matching the default displayed by the form.
         */
        if (! $request->filled('starts_at')) {
            $request->merge([
                'starts_at' => now()->toDateString(),
            ]);
        }

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($organization->id);

        /*
         * DEFAULT_ORGANIZATION_ADMIN_BILLING_OWNER
         *
         * When no billing owner is submitted, use the oldest active
         * Organization Admin belonging to this organization. An explicitly
         * selected valid owner continues to take precedence.
         */
        if (! $request->filled('billing_owner_user_id')) {
            $defaultBillingOwner = $organization
                ->users()
                ->where('is_active', true)
                ->whereHas(
                    'roles',
                    function ($query) use (
                        $organization
                    ): void {
                        $query
                            ->where(
                                'roles.organization_id',
                                $organization->id
                            )
                            ->where(
                                'roles.guard_name',
                                'web'
                            )
                            ->where(
                                'roles.name',
                                \App\Enums\OrganizationRole::
                                    ORGANIZATION_ADMINISTRATOR
                                    ->label()
                            );
                    }
                )
                ->orderBy('users.created_at')
                ->orderBy('users.id')
                ->first();

            if ($defaultBillingOwner !== null) {
                $request->merge([
                    'billing_owner_user_id' =>
                        $defaultBillingOwner->id,
                ]);
            }
        }

        $validated = $request->validate([
            'subscription_plan_id' => [
                'required',
                'integer',

                Rule::exists(
                    'subscription_plans',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where('is_active', true)
                ),
            ],

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

            'starts_at' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'trial_ends_at' => [
                'nullable',
                'date_format:Y-m-d',
                'after:starts_at',
            ],
        ]);

        $plan = SubscriptionPlan::query()
            ->whereKey(
                (int) $validated['subscription_plan_id']
            )
            ->where('is_active', true)
            ->firstOrFail();

        $billingOwner = User::query()
            ->whereKey(
                (int) $validated['billing_owner_user_id']
            )
            ->where(
                'organization_id',
                $organization->id
            )
            ->where('is_active', true)
            ->firstOrFail();

        $startsAt = Carbon::createFromFormat(
            'Y-m-d',
            $validated['starts_at']
        )->startOfDay();

        $trialEndsAt = filled(
            $validated['trial_ends_at'] ?? null
        )
            ? Carbon::createFromFormat(
                'Y-m-d',
                $validated['trial_ends_at']
            )->startOfDay()
            : null;

        DB::transaction(function () use (
            $organization,
            $plan,
            $billingOwner,
            $startsAt,
            $trialEndsAt
        ): void {
            $lockedOrganization = Organization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedOrganization
                    ->subscription()
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        'This organization already has a subscription record.',
                ]);
            }

            $subscription = $lockedOrganization
                ->subscription()
                ->create([
                    'subscription_plan_id' =>
                        $plan->id,

                    'billing_owner_user_id' =>
                        $billingOwner->id,

                    'status' =>
                        OrganizationSubscriptionStatus::TRIALING,

                    'payment_status' =>
                        SubscriptionPaymentStatus::UNPAID,

                    'starts_at' =>
                        $startsAt,

                    'trial_ends_at' =>
                        $trialEndsAt,
                ]);

            $this->activityLogger->log(
                action:
                    'organization.subscription_created',

                description:
                    "Initial subscription assigned on the "
                    ."{$plan->name} plan.",

                subject:
                    $subscription,

                organizationId:
                    $lockedOrganization->id,

                properties: [
                    'plan' => [
                        'id' =>
                            $plan->id,

                        'name' =>
                            $plan->name,

                        'slug' =>
                            $plan->slug,
                    ],

                    'billing_owner' => [
                        'user_id' =>
                            $billingOwner->id,

                        'name' =>
                            $billingOwner->name,

                        'email' =>
                            $billingOwner->email,
                    ],

                    'status' =>
                        OrganizationSubscriptionStatus::TRIALING
                            ->value,

                    'payment_status' =>
                        SubscriptionPaymentStatus::UNPAID
                            ->value,

                    'starts_at' =>
                        $subscription
                            ->starts_at
                            ?->toIso8601String(),

                    'trial_ends_at' =>
                        $subscription
                            ->trial_ends_at
                            ?->toIso8601String(),
                ],
            );
        });

        return redirect()
            ->route(
                'platform.organizations.show',
                $organization
            )
            ->with(
                'success',
                'Initial organization subscription created successfully.'
            );
    }

    /**
     * Assign an active subscription plan to an organization.
     *
     * Existing users, roles, and kiosks remain unchanged even when their
     * current usage exceeds the newly selected plan.
     */
    public function updateSubscriptionPlan(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $validated = $request->validate([
            'subscription_plan_id' => [
                'required',
                'integer',

                Rule::exists(
                    'subscription_plans',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where('is_active', true)
                ),
            ],
        ]);

        $subscription = $organization
            ->subscription()
            ->with('plan')
            ->firstOrFail();

        $previousPlan = $subscription->plan;

        abort_if(
            $previousPlan === null,
            409,
            'The current subscription plan is unavailable.'
        );

        $newPlan = SubscriptionPlan::query()
            ->whereKey(
                (int) $validated['subscription_plan_id']
            )
            ->where('is_active', true)
            ->firstOrFail();

        /*
         * Re-selecting the current plan is not a meaningful change and
         * must not produce a duplicate audit event.
         */
        if (
            $subscription->subscription_plan_id
            === $newPlan->id
        ) {
            return redirect()
                ->route(
                    'platform.organizations.show',
                    $organization
                )
                ->with(
                    'success',
                    'Organization is already assigned to this plan.'
                );
        }

        $usage = $this->subscriptionUsage($organization);

        $exceededLimits = $this->exceededLimitsForPlan(
            $newPlan,
            $usage
        );

        /*
         * The plan change and audit record must either both succeed
         * or both be rolled back.
         */
        DB::transaction(function () use (
            $subscription,
            $organization,
            $previousPlan,
            $newPlan,
            $usage,
            $exceededLimits
        ): void {
            $subscription->update([
                'subscription_plan_id' => $newPlan->id,
            ]);

            $this->activityLogger->log(
                action:
                    'organization.subscription_plan_changed',
                description:
                    "Subscription plan changed from "
                    ."{$previousPlan->name} to {$newPlan->name}.",
                subject: $subscription,
                organizationId: $organization->id,
                properties: [
                    'old' => [
                        'plan_id' => $previousPlan->id,
                        'plan_name' => $previousPlan->name,
                        'plan_slug' => $previousPlan->slug,
                    ],
                    'new' => [
                        'plan_id' => $newPlan->id,
                        'plan_name' => $newPlan->name,
                        'plan_slug' => $newPlan->slug,
                    ],
                    'usage_snapshot' => $usage,
                    'exceeded_limits' => $exceededLimits,
                ],
            );
        });

        return redirect()
            ->route(
                'platform.organizations.show',
                $organization
            )
            ->with(
                'success',
                'Organization subscription plan updated successfully.'
            );
    }

    /**
     * Renew and reactivate an organization's subscription.
     *
     * The existing plan, organization data, and any Platform Admin bypass
     * remain unchanged.
     */
    public function updateSubscriptionRenewal(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $validated = $request->validate([
            'current_period_starts_at' => [
                'required',
                'date_format:d/m/Y,Y-m-d\TH:i',
            ],

            'current_period_ends_at' => [
                'required',
                'date_format:d/m/Y,Y-m-d\TH:i',
            ],

            'ends_at' => [
                'nullable',
                'date_format:d/m/Y,Y-m-d\TH:i',
            ],
        ]);

        $subscription = $organization
            ->subscription()
            ->firstOrFail();

        /*
         * The browser form uses dd/mm/yyyy. Existing ISO datetime
         * submissions remain accepted for backwards compatibility.
         * Date-only values are stored at midnight.
         */
        $parseSubscriptionDate =
            static function (string $value): Carbon {
                if (str_contains($value, '/')) {
                    return Carbon::createFromFormat(
                        'd/m/Y',
                        $value
                    )->startOfDay();
                }

                return Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    $value
                );
            };

        $periodStart = $parseSubscriptionDate(
            $validated['current_period_starts_at']
        );

        $periodEnd = $parseSubscriptionDate(
            $validated['current_period_ends_at']
        );

        $finalEnd = filled($validated['ends_at'] ?? null)
            ? $parseSubscriptionDate(
                $validated['ends_at']
            )
            : null;

        if (! $periodEnd->greaterThan($periodStart)) {
            throw ValidationException::withMessages([
                'current_period_ends_at' =>
                    'The period end date must be after the period start date.',
            ]);
        }

        if (
            $finalEnd !== null
            && $finalEnd->lessThan($periodEnd)
        ) {
            throw ValidationException::withMessages([
                'ends_at' =>
                    'The final subscription end date cannot be before the period end date.',
            ]);
        }

        $datesMatch = static function (
            ?Carbon $current,
            ?Carbon $expected
        ): bool {
            if ($current === null || $expected === null) {
                return $current === null
                    && $expected === null;
            }

            return $current->equalTo($expected);
        };

        $isUnchanged =
            $subscription->status
                === OrganizationSubscriptionStatus::ACTIVE
            && $subscription->payment_status
                === SubscriptionPaymentStatus::PAID
            && $subscription->trial_ends_at === null
            && $subscription->cancelled_at === null
            && $datesMatch(
                $subscription->current_period_starts_at,
                $periodStart
            )
            && $datesMatch(
                $subscription->current_period_ends_at,
                $periodEnd
            )
            && $datesMatch(
                $subscription->ends_at,
                $finalEnd
            );

        if ($isUnchanged) {
            return redirect()
                ->route(
                    'platform.organizations.show',
                    $organization
                )
                ->with(
                    'success',
                    'The subscription already uses these renewal dates.'
                );
        }

        $oldValues = [
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

        DB::transaction(function () use (
            $subscription,
            $organization,
            $periodStart,
            $periodEnd,
            $finalEnd,
            $oldValues
        ): void {
            $subscription->update([
                'status' =>
                    OrganizationSubscriptionStatus::ACTIVE,

                'payment_status' =>
                    SubscriptionPaymentStatus::PAID,

                'trial_ends_at' => null,

                'current_period_starts_at' =>
                    $periodStart,

                'current_period_ends_at' =>
                    $periodEnd,

                'cancelled_at' => null,

                'ends_at' =>
                    $finalEnd,
            ]);

            $subscription->refresh();

            $this->activityLogger->log(
                action:
                    'organization.subscription_renewed',

                description:
                    'Organization subscription renewed.',

                subject:
                    $subscription,

                organizationId:
                    $organization->id,

                properties: [
                    'old' =>
                        $oldValues,

                    'new' => [
                        'status' =>
                            $subscription->status?->value,

                        'payment_status' =>
                            $subscription
                                ->payment_status
                                ?->value,

                        'trial_ends_at' =>
                            $subscription
                                ->trial_ends_at
                                ?->toIso8601String(),

                        'current_period_starts_at' =>
                            $subscription
                                ->current_period_starts_at
                                ?->toIso8601String(),

                        'current_period_ends_at' =>
                            $subscription
                                ->current_period_ends_at
                                ?->toIso8601String(),

                        'cancelled_at' =>
                            $subscription
                                ->cancelled_at
                                ?->toIso8601String(),

                        'ends_at' =>
                            $subscription
                                ->ends_at
                                ?->toIso8601String(),
                    ],
                ],
            );
        });

        return redirect()
            ->route(
                'platform.organizations.show',
                $organization
            )
            ->with(
                'success',
                'Organization subscription renewed successfully.'
            );
    }

    /**
     * Suspend an active organization subscription immediately.
     */
    public function suspendSubscription(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $changed = DB::transaction(function () use (
            $organization,
            $validated
        ): bool {
            $subscription = $organization
                ->subscription()
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $subscription->status
                    === OrganizationSubscriptionStatus::SUSPENDED
            ) {
                return false;
            }

            if (
                $subscription->status
                    !== OrganizationSubscriptionStatus::ACTIVE
            ) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        'Only an active subscription can be suspended.',
                ]);
            }

            $oldValues =
                $this->subscriptionLifecycleSnapshot(
                    $subscription
                );

            $subscription->update([
                'status' =>
                    OrganizationSubscriptionStatus::SUSPENDED,
            ]);

            $subscription->refresh();

            $this->activityLogger->log(
                action:
                    'organization.subscription_suspended',

                description:
                    'Organization subscription suspended.',

                subject:
                    $subscription,

                organizationId:
                    $organization->id,

                properties: [
                    'reason' =>
                        trim($validated['reason']),

                    'old' =>
                        $oldValues,

                    'new' =>
                        $this->subscriptionLifecycleSnapshot(
                            $subscription
                        ),
                ],
            );

            return true;
        });

        return redirect()
            ->route(
                'platform.organizations.show',
                $organization
            )
            ->with(
                'success',
                $changed
                    ? 'Organization subscription suspended successfully.'
                    : 'Organization subscription is already suspended.'
            );
    }

    /**
     * Resume a suspended subscription when payment and dates remain valid.
     */
    public function resumeSubscription(
        Organization $organization
    ): RedirectResponse {
        $changed = DB::transaction(
            function () use ($organization): bool {
                $subscription = $organization
                    ->subscription()
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $subscription->status
                        !== OrganizationSubscriptionStatus::SUSPENDED
                ) {
                    return false;
                }

                if (
                    $subscription->payment_status
                        !== SubscriptionPaymentStatus::PAID
                ) {
                    throw ValidationException::withMessages([
                        'subscription' =>
                            'The subscription must be paid before it can be resumed.',
                    ]);
                }

                if (
                    $subscription->current_period_ends_at !== null
                    && ! $subscription
                        ->current_period_ends_at
                        ->isFuture()
                ) {
                    throw ValidationException::withMessages([
                        'subscription' =>
                            'The billing period has expired. Renew the subscription before resuming it.',
                    ]);
                }

                if (
                    $subscription->ends_at !== null
                    && ! $subscription->ends_at->isFuture()
                ) {
                    throw ValidationException::withMessages([
                        'subscription' =>
                            'The subscription end date has passed. Renew it before resuming.',
                    ]);
                }

                $oldValues =
                    $this->subscriptionLifecycleSnapshot(
                        $subscription
                    );

                $subscription->update([
                    'status' =>
                        OrganizationSubscriptionStatus::ACTIVE,
                ]);

                $subscription->refresh();

                $this->activityLogger->log(
                    action:
                        'organization.subscription_resumed',

                    description:
                        'Organization subscription resumed.',

                    subject:
                        $subscription,

                    organizationId:
                        $organization->id,

                    properties: [
                        'old' =>
                            $oldValues,

                        'new' =>
                            $this->subscriptionLifecycleSnapshot(
                                $subscription
                            ),
                    ],
                );

                return true;
            }
        );

        return redirect()
            ->route(
                'platform.organizations.show',
                $organization
            )
            ->with(
                'success',
                $changed
                    ? 'Organization subscription resumed successfully.'
                    : 'Organization subscription is not suspended.'
            );
    }

    /**
     * Cancel a subscription immediately or at its billing-period end.
     */
    public function cancelSubscription(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $validated = $request->validate([
            'mode' => [
                'required',
                Rule::in([
                    'immediate',
                    'period_end',
                ]),
            ],

            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $result = DB::transaction(function () use (
            $organization,
            $validated
        ): array {
            $subscription = $organization
                ->subscription()
                ->lockForUpdate()
                ->firstOrFail();

            $mode = $validated['mode'];
            $reason = trim($validated['reason']);

            if ($mode === 'immediate') {
                if (
                    $subscription->status
                        === OrganizationSubscriptionStatus::CANCELLED
                ) {
                    return [
                        'changed' => false,
                        'message' =>
                            'Organization subscription is already cancelled.',
                    ];
                }

                $oldValues =
                    $this->subscriptionLifecycleSnapshot(
                        $subscription
                    );

                $cancelledAt = now();

                $subscription->update([
                    'status' =>
                        OrganizationSubscriptionStatus::CANCELLED,

                    'cancelled_at' =>
                        $cancelledAt,

                    'ends_at' =>
                        $cancelledAt,
                ]);

                $subscription->refresh();

                $this->activityLogger->log(
                    action:
                        'organization.subscription_cancelled',

                    description:
                        'Organization subscription cancelled immediately.',

                    subject:
                        $subscription,

                    organizationId:
                        $organization->id,

                    properties: [
                        'cancellation_mode' =>
                            'immediate',

                        'reason' =>
                            $reason,

                        'old' =>
                            $oldValues,

                        'new' =>
                            $this->subscriptionLifecycleSnapshot(
                                $subscription
                            ),
                    ],
                );

                return [
                    'changed' => true,
                    'message' =>
                        'Organization subscription cancelled immediately.',
                ];
            }

            if (
                $subscription->status
                    !== OrganizationSubscriptionStatus::ACTIVE
            ) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        'Only an active subscription can be cancelled at period end.',
                ]);
            }

            if (
                $subscription->current_period_ends_at === null
                || ! $subscription
                    ->current_period_ends_at
                    ->isFuture()
            ) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        'A future billing-period end is required for scheduled cancellation.',
                ]);
            }

            $alreadyScheduled =
                $subscription->cancelled_at !== null
                && $subscription->ends_at !== null
                && $subscription->ends_at->equalTo(
                    $subscription->current_period_ends_at
                );

            if ($alreadyScheduled) {
                return [
                    'changed' => false,
                    'message' =>
                        'Subscription cancellation is already scheduled for period end.',
                ];
            }

            $oldValues =
                $this->subscriptionLifecycleSnapshot(
                    $subscription
                );

            $subscription->update([
                'cancelled_at' =>
                    now(),

                'ends_at' =>
                    $subscription->current_period_ends_at,
            ]);

            $subscription->refresh();

            $this->activityLogger->log(
                action:
                    'organization.subscription_cancellation_scheduled',

                description:
                    'Organization subscription cancellation scheduled for period end.',

                subject:
                    $subscription,

                organizationId:
                    $organization->id,

                properties: [
                    'cancellation_mode' =>
                        'period_end',

                    'reason' =>
                        $reason,

                    'old' =>
                        $oldValues,

                    'new' =>
                        $this->subscriptionLifecycleSnapshot(
                            $subscription
                        ),
                ],
            );

            return [
                'changed' => true,
                'message' =>
                    'Subscription cancellation scheduled for period end.',
            ];
        });

        return redirect()
            ->route(
                'platform.organizations.show',
                $organization
            )
            ->with(
                'success',
                $result['message']
            );
    }

    /**
     * Allow an organization to operate without confirmed payment.
     */
    public function approveSubscriptionBypass(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $subscription = $organization
            ->subscription()
            ->firstOrFail();

        $subscription->update([
            'bypass_approved_at' => now(),
            'bypass_approved_by_user_id' =>
                $request->user()->id,
            'bypass_reason' => trim($validated['reason']),
        ]);

        return redirect()
            ->route(
                'platform.organizations.show',
                $organization
            )
            ->with(
                'success',
                'Organization subscription bypass approved.'
            );
    }

    /**
     * Remove an organization's payment bypass.
     */
    public function revokeSubscriptionBypass(
        Organization $organization
    ): RedirectResponse {
        $subscription = $organization
            ->subscription()
            ->firstOrFail();

        $subscription->update([
            'bypass_approved_at' => null,
            'bypass_approved_by_user_id' => null,
            'bypass_reason' => null,
        ]);

        return redirect()
            ->route(
                'platform.organizations.show',
                $organization
            )
            ->with(
                'success',
                'Organization subscription bypass revoked.'
            );
    }

    /**
     * Return lifecycle values suitable for activity-log comparison.
     *
     * @return array<string, string|null>
     */
    private function subscriptionLifecycleSnapshot(
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

    /**
     * Return current subscription usage for an organization.
     *
     * Disabled users count toward capacity while archived users remain
     * excluded through the User model's SoftDeletes scope.
     *
     * @return array<string, int>
     */
    private function subscriptionUsage(
        Organization $organization
    ): array {
        return [
            'users' => $organization->users()->count(),

            'consent_managers' => $this->roleUsage(
                $organization->id,
                'Consent Manager'
            ),

            'staff' => $this->roleUsage(
                $organization->id,
                'Staff'
            ),

            'auditors' => $this->roleUsage(
                $organization->id,
                'Auditor'
            ),

            'active_kiosks' => SigningStation::query()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->where('active', true)
                ->count(),
        ];
    }

    /**
     * Count non-archived organization users assigned to a role.
     */
    private function roleUsage(
        int $organizationId,
        string $roleName
    ): int {
        return User::query()
            ->where(
                'users.organization_id',
                $organizationId
            )
            ->whereHas(
                'roles',
                function ($query) use (
                    $organizationId,
                    $roleName
                ): void {
                    $query
                        ->where(
                            'roles.organization_id',
                            $organizationId
                        )
                        ->where('roles.guard_name', 'web')
                        ->where('roles.name', $roleName);
                }
            )
            ->count();
    }

    /**
     * Return only limits exceeded under the selected plan.
     *
     * Null limits represent unlimited capacity and are excluded.
     *
     * @param array<string, int> $usage
     * @return array<string, array{
     *     label: string,
     *     used: int,
     *     limit: int,
     *     overage: int
     * }>
     */
    private function exceededLimitsForPlan(
        SubscriptionPlan $plan,
        array $usage
    ): array {
        $definitions = [
            'users' => [
                'label' => 'Total users',
                'used' => $usage['users'],
                'limit' => $plan->max_users,
            ],
            'consent_managers' => [
                'label' => 'Consent Managers',
                'used' => $usage['consent_managers'],
                'limit' => $plan->max_consent_managers,
            ],
            'staff' => [
                'label' => 'Staff',
                'used' => $usage['staff'],
                'limit' => $plan->max_staff,
            ],
            'auditors' => [
                'label' => 'Auditors',
                'used' => $usage['auditors'],
                'limit' => $plan->max_auditors,
            ],
            'active_kiosks' => [
                'label' => 'Active kiosks',
                'used' => $usage['active_kiosks'],
                'limit' => $plan->max_active_kiosks,
            ],
        ];

        $exceeded = [];

        foreach ($definitions as $key => $definition) {
            if ($definition['limit'] === null) {
                continue;
            }

            $used = (int) $definition['used'];
            $limit = (int) $definition['limit'];
            $overage = max(0, $used - $limit);

            if ($overage === 0) {
                continue;
            }

            $exceeded[$key] = [
                'label' => $definition['label'],
                'used' => $used,
                'limit' => $limit,
                'overage' => $overage,
            ];
        }

        return $exceeded;
    }

    /**
     * Build display-ready usage and overage information.
     *
     * @param array<string, int> $usage
     * @return list<array{
     *     label: string,
     *     used: int,
     *     limit: int,
     *     remaining: int,
     *     overage: int
     * }>
     */
    private function capacityForPlan(
        ?SubscriptionPlan $plan,
        array $usage
    ): array {
        if ($plan === null) {
            return [];
        }

        $definitions = [
            [
                'label' => 'Total users',
                'used' => $usage['users'],
                'limit' => $plan->max_users,
            ],
            [
                'label' => 'Consent Managers',
                'used' => $usage['consent_managers'],
                'limit' => $plan->max_consent_managers,
            ],
            [
                'label' => 'Staff',
                'used' => $usage['staff'],
                'limit' => $plan->max_staff,
            ],
            [
                'label' => 'Auditors',
                'used' => $usage['auditors'],
                'limit' => $plan->max_auditors,
            ],
            [
                'label' => 'Active kiosks',
                'used' => $usage['active_kiosks'],
                'limit' => $plan->max_active_kiosks,
            ],
        ];

        return array_map(
            static function (array $definition): array {
                $used = (int) $definition['used'];
                $limit = (int) $definition['limit'];

                return [
                    'label' => $definition['label'],
                    'used' => $used,
                    'limit' => $limit,
                    'remaining' => max(0, $limit - $used),
                    'overage' => max(0, $used - $limit),
                ];
            },
            $definitions
        );
    }

    /**
     * Display users belonging to a specific organization.
     *
     * The relationship is paginated so the page remains efficient when an
     * organization eventually contains hundreds or thousands of users.
     */
    public function users(Organization $organization): View
    {
        $users = $organization
            ->users()
            ->orderBy('name')
            ->paginate(15);

        return view(
            'platform.organizations.users.index',
            compact('organization', 'users')
        );
    }
}
