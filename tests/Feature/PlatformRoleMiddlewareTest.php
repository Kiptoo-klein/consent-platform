<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private PlatformRole $superAdminRole;

    private PlatformRole $billingRole;

    private PlatformRole $supportRole;

    private PlatformRole $auditorRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PlatformRoleSeeder::class
        );

        $this->superAdminRole =
            $this->role(
                'super-admin'
            );

        $this->billingRole =
            $this->role(
                'billing'
            );

        $this->supportRole =
            $this->role(
                'support'
            );

        $this->auditorRole =
            $this->role(
                'platform-auditor'
            );

        Route::middleware([
            'auth',
            'active.user',
            'platform.role:super-admin',
        ])->get(
            '/__tests/platform-role/super-admin-only',
            fn () => response(
                'Super Admin route'
            )
        );

        Route::middleware([
            'auth',
            'active.user',
            'platform.role:super-admin,billing',
        ])->get(
            '/__tests/platform-role/super-admin-or-billing',
            fn () => response(
                'Billing route'
            )
        );

        Route::middleware([
            'auth',
            'active.user',
            'platform.role:super-admin,support,platform-auditor',
        ])->get(
            '/__tests/platform-role/read-only',
            fn () => response(
                'Read-only route'
            )
        );
    }

    public function test_existing_single_role_syntax_still_allows_super_admin(): void
    {
        $superAdmin =
            $this->createPlatformUser(
                $this->superAdminRole
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->get(
                '/__tests/platform-role/super-admin-only'
            )
            ->assertOk()
            ->assertSeeText(
                'Super Admin route'
            );
    }

    public function test_existing_single_role_syntax_rejects_other_roles(): void
    {
        $billingUser =
            $this->createPlatformUser(
                $this->billingRole
            );

        $this
            ->actingAs(
                $billingUser
            )
            ->get(
                '/__tests/platform-role/super-admin-only'
            )
            ->assertForbidden();
    }

    public function test_multiple_role_syntax_allows_billing_role(): void
    {
        $billingUser =
            $this->createPlatformUser(
                $this->billingRole
            );

        $this
            ->actingAs(
                $billingUser
            )
            ->get(
                '/__tests/platform-role/super-admin-or-billing'
            )
            ->assertOk()
            ->assertSeeText(
                'Billing route'
            );
    }

    public function test_multiple_role_syntax_still_allows_super_admin(): void
    {
        $superAdmin =
            $this->createPlatformUser(
                $this->superAdminRole
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->get(
                '/__tests/platform-role/super-admin-or-billing'
            )
            ->assertOk();
    }

    public function test_role_not_listed_by_middleware_is_rejected(): void
    {
        $supportUser =
            $this->createPlatformUser(
                $this->supportRole
            );

        $this
            ->actingAs(
                $supportUser
            )
            ->get(
                '/__tests/platform-role/super-admin-or-billing'
            )
            ->assertForbidden();
    }

    public function test_read_only_route_accepts_support_and_auditor_roles(): void
    {
        foreach ([
            $this->supportRole,
            $this->auditorRole,
        ] as $role) {
            $user =
                $this->createPlatformUser(
                    $role
                );

            $this
                ->actingAs(
                    $user
                )
                ->get(
                    '/__tests/platform-role/read-only'
                )
                ->assertOk()
                ->assertSeeText(
                    'Read-only route'
                );
        }
    }

    public function test_user_without_platform_role_is_rejected(): void
    {
        $user =
            User::factory()->create([
                'organization_id' =>
                    null,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $this
            ->actingAs(
                $user
            )
            ->get(
                '/__tests/platform-role/read-only'
            )
            ->assertForbidden();
    }

    public function test_organization_user_is_rejected(): void
    {
        $organization =
            Organization::query()->create([
                'name' =>
                    'Middleware Restricted Organization',

                'slug' =>
                    'middleware-restricted-'
                    .Str::lower(
                        Str::random(8)
                    ),
            ]);

        $organizationUser =
            User::factory()->create([
                'organization_id' =>
                    $organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $this
            ->actingAs(
                $organizationUser
            )
            ->get(
                '/__tests/platform-role/read-only'
            )
            ->assertForbidden();
    }

    private function role(
        string $slug
    ): PlatformRole {
        return PlatformRole::query()
            ->where(
                'slug',
                $slug
            )
            ->firstOrFail();
    }

    private function createPlatformUser(
        PlatformRole $role
    ): User {
        return User::factory()->create([
            'organization_id' =>
                null,

            'platform_role_id' =>
                $role->id,

            'is_active' =>
                true,
        ]);
    }
}
