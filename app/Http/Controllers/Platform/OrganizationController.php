<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        $usage = $this->subscriptionUsage($organization);

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
            ->firstOrFail();

        $subscription->update([
            'subscription_plan_id' =>
                (int) $validated['subscription_plan_id'],
        ]);

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
