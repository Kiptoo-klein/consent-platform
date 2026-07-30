<?php

namespace Tests\Feature;

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
            ->assertSeeText('Manage Users')
            ->assertSeeText('Subscription')
            ->assertSeeText('Billing');
        $response->assertSeeTextInOrder([
            'Dashboard',
            'Consent Templates',
            'Consent Records',
            'Signing Stations',
            'Manage Users',
            'Subscription',
            'Billing',
            'Organization Branding',
        ]);

        $response->assertSee(
            route(
                'organization-users.index',
                $this->organization
            ),
            false
        );

        $response->assertSee(
            route('organization-subscription.show'),
            false
        );

        $response->assertSee(
            route('organization-billing.index'),
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

        $navigationResponse = $this
            ->actingAs($staff)
            ->get(route('organization-subscription.show'));

        $navigationResponse
            ->assertOk()
            ->assertDontSeeText('Manage Users')
            ->assertSeeText('Subscription')
            ->assertDontSeeText('Billing');

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


}
