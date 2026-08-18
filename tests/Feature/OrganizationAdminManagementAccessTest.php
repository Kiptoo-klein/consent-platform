<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPaymentStatus;
use App\Http\Middleware\EnsureOrganizationSubscriptionAccess;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrganizationAdminManagementAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $organizationAdmin;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SubscriptionPlanSeeder::class);

        $this->post('/register', [
            'organization_name' =>
                'Organization Management Clinic',
            'name' =>
                'Organization Management Administrator',
            'email' =>
                'organization-management-admin@example.com',
            'password' =>
                'StrongPass1!',
            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->organizationAdmin = User::query()
            ->where(
                'email',
                'organization-management-admin@example.com'
            )
            ->firstOrFail();

        $this->organization = $this
            ->organizationAdmin
            ->organization()
            ->firstOrFail();
    }


    public function test_primary_admin_is_billing_owner_by_default(): void
    {
        $subscription = $this->organization
            ->subscription()
            ->firstOrFail();

        $this->assertSame(
            $this->organizationAdmin->id,
            $subscription->billing_owner_user_id
        );
    }

    public function test_organization_admin_sees_management_navigation(): void
    {
        $response = $this
            ->actingAs($this->organizationAdmin)
            ->get(route('organization-subscription.show'));

        $response
            ->assertOk()
            ->assertSeeText('Settings');

        $response->assertSeeTextInOrder([
            'Dashboard',
            'Consent Templates',
            'Consent Records',
            'Signing Stations',
            'Settings',
        ]);

        $response->assertSee(
            route('organization-settings.index'),
            false
        );

        $response->assertSee(
            'data-navigation-route="organization-settings.index"',
            false
        );

        $response->assertDontSee(
            'data-navigation-route="organization-users.index"',
            false
        );

        $response->assertDontSee(
            'data-navigation-route="organization-subscription.show"',
            false
        );

        $response->assertDontSee(
            'data-navigation-route="organization-subscription-plans.index"',
            false
        );

        $response->assertDontSee(
            'data-navigation-route="organization-billing.index"',
            false
        );

        $response->assertDontSee(
            'data-navigation-route="organization-branding.edit"',
            false
        );
    }

    public function test_organization_admin_can_open_user_management(): void
    {
        $this
            ->actingAs($this->organizationAdmin)
            ->get(
                route(
                    'organization-users.index',
                    $this->organization
                )
            )
            ->assertOk()
            ->assertSeeText('Manage Users')
            ->assertSeeText(
                $this->organizationAdmin->name
            );
    }

    public function test_non_admin_cannot_manage_organization_users(): void
    {
        $staff = User::factory()->create([
            'organization_id' =>
                $this->organization->id,
            'platform_role_id' => null,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(
                $this->organization->id
            );

        $staffRole = Role::query()
            ->where(
                'organization_id',
                $this->organization->id
            )
            ->where('guard_name', 'web')
            ->where('name', 'Staff')
            ->firstOrFail();

        $staff->assignRole($staffRole);

        $this
            ->organization
            ->subscription()
            ->update([
                'payment_status' =>
                    SubscriptionPaymentStatus::PAID,
            ]);

        $navigationResponse = $this
            ->actingAs($staff)
            ->get(route('dashboard'));

        $navigationResponse
            ->assertOk()
            ->assertSeeText('Settings')
            ->assertSee(
                'data-navigation-route="organization-settings.index"',
                false
            )
            ->assertDontSee(
                'data-navigation-route="organization-users.index"',
                false
            )
            ->assertDontSee(
                'data-navigation-route="organization-billing.index"',
                false
            )
            ->assertDontSee(
                'data-navigation-route="organization-branding.edit"',
                false
            );

        $this
            ->actingAs($staff)
            ->get(
                route(
                    'organization-users.index',
                    $this->organization
                )
            )
            ->assertForbidden();
    }

    public function test_admin_cannot_manage_another_organization(): void
    {
        $otherOrganization =
            Organization::query()->create([
                'name' => 'Another Organization',
                'slug' => 'another-organization',
            ]);

        $this
            ->actingAs($this->organizationAdmin)
            ->get(
                route(
                    'organization-users.index',
                    $otherOrganization
                )
            )
            ->assertForbidden();
    }

    public function test_organization_admin_role_is_last_and_warned(): void
    {
        $response = $this
            ->actingAs($this->organizationAdmin)
            ->get(
                route(
                    'organization-users.create',
                    $this->organization
                )
            );

        $response
            ->assertOk()
            ->assertSeeText(
                'Organization Admin — Full access '
                .'(use with caution)'
            )
            ->assertSeeText(
                'Warning: Organization Admin grants '
                .'full organizational control.'
            )
            ->assertSeeText(
                'Assigning this role does not automatically '
                .'change the current billing owner.'
            );

        $html = $response->getContent();

        preg_match_all(
            '/<option\b[^>]*>(.*?)<\/option>/s',
            $html,
            $matches
        );

        $roleOptions = array_values(
            array_filter(
                array_map(
                    static function (string $option): string {
                        $text = html_entity_decode(
                            strip_tags($option)
                        );

                        return trim(
                            preg_replace(
                                '/\s+/',
                                ' ',
                                $text
                            ) ?? ''
                        );
                    },
                    $matches[1]
                ),
                static fn (string $option): bool =>
                    $option !== ''
                    && $option !== 'Select a role'
            )
        );

        $this->assertNotEmpty($roleOptions);

        $this->assertSame(
            'Organization Admin — Full access '
                .'(use with caution)',
            end($roleOptions)
        );

        $this->assertMatchesRegularExpression(
            '/<option[^>]*'
                .'text-red-700'
                .'[^>]*>\s*Organization Admin/s',
            $html
        );
    }


    public function test_only_organization_admin_can_see_and_access_organization_branding(): void
    {
        $this->withoutMiddleware(
            EnsureOrganizationSubscriptionAccess::class
        );

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->get(
                route(
                    'organization-subscription.show'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Settings'
            )
            ->assertSee(
                'data-navigation-route="organization-settings.index"',
                false
            )
            ->assertDontSee(
                'data-navigation-route="organization-branding.edit"',
                false
            );

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->get(
                route(
                    'organization-branding.edit'
                )
            )
            ->assertOk();

        app(
            PermissionRegistrar::class
        )->setPermissionsTeamId(
            $this->organization->id
        );

        $ordinaryRole =
            Role::query()->firstOrCreate([
                'organization_id' =>
                    $this->organization->id,

                'name' =>
                    OrganizationRole::
                        WORKFLOW_OPERATOR
                        ->label(),

                'guard_name' =>
                    'web',
            ]);

        $ordinaryUser =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $ordinaryUser->assignRole(
            $ordinaryRole
        );

        $this
            ->actingAs(
                $ordinaryUser
            )
            ->get(
                route(
                    'dashboard'
                )
            )
            ->assertOk()
            ->assertDontSeeText(
                'Organization Branding'
            )
            ->assertDontSee(
                'data-navigation-route="organization-branding.edit"',
                false
            );

        $this
            ->actingAs(
                $ordinaryUser
            )
            ->get(
                route(
                    'organization-branding.edit'
                )
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $ordinaryUser
            )
            ->put(
                route(
                    'organization-branding.update'
                ),
                []
            )
            ->assertForbidden();
    }


    public function test_all_organization_users_can_open_subscription_usage(): void
    {
        app(
            PermissionRegistrar::class
        )->setPermissionsTeamId(
            $this->organization->id
        );

        $staffRole =
            Role::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->where(
                    'guard_name',
                    'web'
                )
                ->where(
                    'name',
                    OrganizationRole::
                        WORKFLOW_OPERATOR
                        ->label()
                )
                ->firstOrFail();

        $billingOwner =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $billingOwner->assignRole(
            $staffRole
        );

        $ordinaryUser =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $ordinaryUser->assignRole(
            $staffRole
        );

        $this
            ->organization
            ->subscription()
            ->update([
                'billing_owner_user_id' =>
                    $billingOwner->id,
            ]);

        foreach ([
            $this->organizationAdmin,
            $billingOwner,
            $ordinaryUser,
        ] as $user) {
            $this
                ->actingAs($user)
                ->get(
                    route(
                        'organization-subscription.show'
                    )
                )
                ->assertOk()
                ->assertSeeText(
                    'Subscription usage'
                )
                ->assertSee(
                    'data-navigation-route="organization-settings.index"',
                    false
                )
                ->assertDontSee(
                    'data-navigation-route="organization-subscription.show"',
                    false
                );
        }

        $this
            ->actingAs($ordinaryUser)
            ->get(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertForbidden();

        $this
            ->actingAs($ordinaryUser)
            ->get(
                route(
                    'organization-billing.index'
                )
            )
            ->assertForbidden();
    }

    public function test_settings_page_shows_role_appropriate_management_links(): void
    {
        app(
            PermissionRegistrar::class
        )->setPermissionsTeamId(
            $this->organization->id
        );

        $staffRole =
            Role::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->where(
                    'guard_name',
                    'web'
                )
                ->where(
                    'name',
                    OrganizationRole::
                        WORKFLOW_OPERATOR
                        ->label()
                )
                ->firstOrFail();

        $billingOwner =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $billingOwner->assignRole(
            $staffRole
        );

        $ordinaryUser =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $ordinaryUser->assignRole(
            $staffRole
        );

        $this
            ->organization
            ->subscription()
            ->update([
                'billing_owner_user_id' =>
                    $billingOwner->id,
            ]);

        /*
         * Organization Admin:
         * Team, Branding, Subscription, Plans and Billing.
         */
        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->get(
                route(
                    'organization-settings.index'
                )
            )
            ->assertOk()
            ->assertSee(
                route(
                    'organization-users.index',
                    $this->organization
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-branding.edit'
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-subscription.show'
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-subscription-plans.index'
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-billing.index'
                ),
                false
            );

        /*
         * Billing Owner:
         * Subscription, Plans and Billing only.
         */
        $this
            ->actingAs(
                $billingOwner
            )
            ->get(
                route(
                    'organization-settings.index'
                )
            )
            ->assertOk()
            ->assertDontSee(
                route(
                    'organization-users.index',
                    $this->organization
                ),
                false
            )
            ->assertDontSee(
                route(
                    'organization-branding.edit'
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-subscription.show'
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-subscription-plans.index'
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-billing.index'
                ),
                false
            );

        /*
         * Ordinary organization user:
         * Subscription & Usage only.
         */
        $this
            ->actingAs(
                $ordinaryUser
            )
            ->get(
                route(
                    'organization-settings.index'
                )
            )
            ->assertOk()
            ->assertSee(
                route(
                    'organization-subscription.show'
                ),
                false
            )
            ->assertDontSee(
                route(
                    'organization-users.index',
                    $this->organization
                ),
                false
            )
            ->assertDontSee(
                route(
                    'organization-branding.edit'
                ),
                false
            )
            ->assertDontSee(
                route(
                    'organization-subscription-plans.index'
                ),
                false
            )
            ->assertDontSee(
                route(
                    'organization-billing.index'
                ),
                false
            );
    }


    public function test_new_evaluation_settings_do_not_show_subscription_recovery_warning(): void
    {
        $subscription = $this
            ->organization
            ->subscription()
            ->firstOrFail();

        $this->assertTrue(
            $subscription->isEvaluation()
        );

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->get(
                route(
                    'organization-settings.index'
                )
            )
            ->assertOk()
            ->assertDontSeeText(
                'Subscription access needs attention'
            )
            ->assertDontSeeText(
                'Requires an active subscription'
            )
            ->assertSee(
                route(
                    'organization-branding.edit'
                ),
                false
            );
    }

    public function test_expired_subscription_settings_show_recovery_and_lock_branding(): void
    {
        $subscription = $this
            ->organization
            ->subscription()
            ->firstOrFail();

        $subscription->update([
            'status' => 'expired',
            'payment_status' => 'paid',
            'current_period_ends_at' =>
                now()->subMinute(),
            'ends_at' =>
                now()->subMinute(),
        ]);

        $this->assertFalse(
            $subscription
                ->fresh()
                ->allowsOrganizationAccess()
        );

        $response = $this
            ->actingAs(
                $this->organizationAdmin->fresh()
            )
            ->get(
                route(
                    'organization-settings.index'
                )
            );

        $response
            ->assertOk()
            ->assertSeeText(
                'Subscription access needs attention'
            )
            ->assertSeeText(
                'Requires an active subscription'
            )
            ->assertSee(
                route(
                    'organization-subscription.show'
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-subscription-plans.index'
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-billing.index'
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-users.index',
                    $this->organization
                ),
                false
            )
            ->assertDontSee(
                route(
                    'organization-branding.edit'
                ),
                false
            );

        $this
            ->get(
                route(
                    'organization-branding.edit'
                )
            )
            ->assertRedirect(
                route(
                    'organization-subscription.show'
                )
            );
    }

}
