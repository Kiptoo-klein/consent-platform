<?php

use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(
        PlatformRoleSeeder::class
    );
});

function platformArchiveActor(
    string $roleSlug
): User {
    $role =
        PlatformRole::query()
            ->where(
                'slug',
                $roleSlug
            )
            ->firstOrFail();

    return User::factory()->create([
        'organization_id' =>
            null,

        'platform_role_id' =>
            $role->id,

        'is_active' =>
            true,

        'email_verified_at' =>
            now(),
    ]);
}

function platformArchiveOrganization(): Organization
{
    return Organization::query()->create([
        'name' =>
            'Platform Archive Organization',

        'slug' =>
            'platform-archive-organization',
    ]);
}

test(
    'platform super admin can archive whole organization without archiving users',
    function () {
        $superAdmin =
            platformArchiveActor(
                'super-admin'
            );

        $organization =
            platformArchiveOrganization();

        $organizationUser =
            User::factory()->create([
                'organization_id' =>
                    $organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,

                'email_verified_at' =>
                    now(),
            ]);

        $this
            ->actingAs($superAdmin)
            ->patch(
                route(
                    'platform.organizations.archive',
                    $organization
                )
            )
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $organization
                )
            )
            ->assertSessionHas(
                'success',
                'Organization archived successfully.'
            );

        $organization->refresh();

        expect(
            $organization->archived_at
        )->not->toBeNull();

        $preservedUser =
            User::withTrashed()
                ->findOrFail(
                    $organizationUser->id
                );

        expect(
            $preservedUser->trashed()
        )->toBeFalse();

        expect(
            $preservedUser->is_active
        )->toBeTrue();
    }
);

test(
    'non super admin platform role cannot archive organization',
    function () {
        $supportUser =
            platformArchiveActor(
                'support'
            );

        $organization =
            platformArchiveOrganization();

        $this
            ->actingAs($supportUser)
            ->patch(
                route(
                    'platform.organizations.archive',
                    $organization
                )
            )
            ->assertForbidden();

        expect(
            $organization
                ->fresh()
                ->archived_at
        )->toBeNull();
    }
);

test(
    'platform super admin sees archive action for active organization',
    function () {
        $superAdmin =
            platformArchiveActor(
                'super-admin'
            );

        $organization =
            platformArchiveOrganization();

        $this
            ->actingAs($superAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $organization
                )
            )
            ->assertOk()
            ->assertSee(
                'data-organization-archive-control',
                false
            )
            ->assertSeeText(
                'Archive Organization'
            )
            ->assertSeeText(
                'Individual user accounts are not archived.'
            )
            ->assertDontSee(
                'data-organization-restore',
                false
            );
    }
);

test(
    'archived organization replaces archive action with restore action',
    function () {
        $superAdmin =
            platformArchiveActor(
                'super-admin'
            );

        $organization =
            platformArchiveOrganization();

        $organization
            ->forceFill([
                'archived_at' =>
                    now(),
            ])
            ->save();

        $this
            ->actingAs($superAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $organization
                )
            )
            ->assertOk()
            ->assertDontSee(
                'data-organization-archive-control',
                false
            )
            ->assertSee(
                'data-organization-restore',
                false
            )
            ->assertSeeText(
                'Restore Organization'
            );
    }
);

test(
    'existing individual user archive route remains available',
    function () {
        expect(
            route(
                'platform.organizations.users.destroy',
                [
                    'organization' => 10,
                    'user' => 20,
                ],
                false
            )
        )->toBe(
            '/platform/organizations/10/users/20'
        );
    }
);
