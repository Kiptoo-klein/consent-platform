<?php

use App\Enums\OrganizationRole;
use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\User;
use App\Notifications\OrganizationAccessStatusChanged;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

function platformArchiveOrganizationAdmin(
    Organization $organization,
    array $overrides = []
): User {
    app(PermissionRegistrar::class)
        ->setPermissionsTeamId(
            $organization->id
        );

    $role =
        Role::query()
            ->firstOrCreate([
                'name' =>
                    OrganizationRole::
                        ORGANIZATION_ADMINISTRATOR
                        ->label(),
                'guard_name' =>
                    'web',
                'organization_id' =>
                    $organization->id,
            ]);

    $user =
        User::factory()->create(
            array_merge(
                [
                    'organization_id' =>
                        $organization->id,
                    'platform_role_id' =>
                        null,
                    'is_active' =>
                        true,
                    'email_verified_at' =>
                        now(),
                ],
                $overrides
            )
        );

    $user->assignRole($role);

    return $user;
}

test(
    'platform super admin archives organization with audited reason and notifies admin',
    function () {
        Notification::fake();

        $superAdmin =
            platformArchiveActor(
                'super-admin'
            );

        $organization =
            platformArchiveOrganization();

        $organizationAdmin =
            platformArchiveOrganizationAdmin(
                $organization
            );

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

        $reason =
            'Customer requested organization closure.';

        $this
            ->actingAs($superAdmin)
            ->patch(
                route(
                    'platform.organizations.archive',
                    $organization
                ),
                [
                    'archive_reason' =>
                        $reason,
                ]
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

        $activity =
            ActivityLog::query()
                ->where(
                    'action',
                    'organization.archived'
                )
                ->firstOrFail();

        $properties =
            json_decode(
                (string) $activity
                    ->getRawOriginal(
                        'properties'
                    ),
                true
            );

        expect(
            $properties['reason'] ?? null
        )->toBe($reason);

        Notification::assertSentTo(
            $organizationAdmin,
            OrganizationAccessStatusChanged::class,
            function (
                OrganizationAccessStatusChanged $notification
            ) use (
                $organization,
                $reason
            ): bool {
                return
                    $notification->status
                        === 'archived'
                    && $notification
                        ->organizationName
                        === $organization->name
                    && $notification
                        ->archiveReason
                        === $reason;
            }
        );
    }
);

test(
    'archive requires a meaningful reason',
    function () {
        Notification::fake();

        $superAdmin =
            platformArchiveActor(
                'super-admin'
            );

        $organization =
            platformArchiveOrganization();

        platformArchiveOrganizationAdmin(
            $organization
        );

        $this
            ->actingAs($superAdmin)
            ->from(
                route(
                    'platform.organizations.show',
                    $organization
                )
            )
            ->patch(
                route(
                    'platform.organizations.archive',
                    $organization
                ),
                [
                    'archive_reason' =>
                        'short',
                ]
            )
            ->assertSessionHasErrors(
                'archive_reason'
            );

        expect(
            $organization
                ->fresh()
                ->archived_at
        )->toBeNull();

        Notification::assertNothingSent();
    }
);

test(
    'only active verified organization admins receive archive notification',
    function () {
        Notification::fake();

        $superAdmin =
            platformArchiveActor(
                'super-admin'
            );

        $organization =
            platformArchiveOrganization();

        $active =
            platformArchiveOrganizationAdmin(
                $organization,
                [
                    'email' =>
                        'active-admin@example.com',
                ]
            );

        $inactive =
            platformArchiveOrganizationAdmin(
                $organization,
                [
                    'email' =>
                        'inactive-admin@example.com',
                    'is_active' =>
                        false,
                ]
            );

        $unverified =
            platformArchiveOrganizationAdmin(
                $organization,
                [
                    'email' =>
                        'unverified-admin@example.com',
                    'email_verified_at' =>
                        null,
                ]
            );

        $this
            ->actingAs($superAdmin)
            ->patch(
                route(
                    'platform.organizations.archive',
                    $organization
                ),
                [
                    'archive_reason' =>
                        'Organization access must be temporarily closed.',
                ]
            )
            ->assertSessionHasNoErrors();

        Notification::assertSentTo(
            $active,
            OrganizationAccessStatusChanged::class
        );

        Notification::assertNotSentTo(
            $inactive,
            OrganizationAccessStatusChanged::class
        );

        Notification::assertNotSentTo(
            $unverified,
            OrganizationAccessStatusChanged::class
        );
    }
);

test(
    'organization administrator is notified after restoration',
    function () {
        Notification::fake();

        $superAdmin =
            platformArchiveActor(
                'super-admin'
            );

        $organization =
            platformArchiveOrganization();

        $organizationAdmin =
            platformArchiveOrganizationAdmin(
                $organization
            );

        $organization
            ->forceFill([
                'archived_at' =>
                    now()->subHour(),
            ])
            ->save();

        $this
            ->actingAs($superAdmin)
            ->patch(
                route(
                    'platform.organizations.restore',
                    $organization
                )
            )
            ->assertSessionHas(
                'success',
                'Organization restored successfully.'
            );

        expect(
            $organization
                ->fresh()
                ->archived_at
        )->toBeNull();

        Notification::assertSentTo(
            $organizationAdmin,
            OrganizationAccessStatusChanged::class,
            fn (
                OrganizationAccessStatusChanged $notification
            ): bool =>
                $notification->status
                    === 'restored'
                && $notification
                    ->archiveReason
                    === null
        );
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
                ),
                [
                    'archive_reason' =>
                        'This request must remain forbidden.',
                ]
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
    'platform super admin sees archive reason control for active organization',
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
                'Archive reason'
            )
            ->assertSee(
                'name="archive_reason"',
                false
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
    'organization access notification is queue and quota safe',
    function () {
        $notification =
            new OrganizationAccessStatusChanged(
                status:
                    'archived',
                organizationName:
                    'Example Organization',
                performedByName:
                    'Platform Administrator',
                archiveReason:
                    'Customer requested closure.',
                changedAt:
                    'Aug 23, 2026 21:00 EAT'
            );

        expect(
            $notification->tries
        )->toBe(1000);

        expect(
            $notification->maxExceptions
        )->toBe(3);

        $middleware =
            $notification->middleware();

        expect($middleware)
            ->toHaveCount(1);

        expect($middleware[0])
            ->toBeInstanceOf(
                EnforceEmailQuota::class
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
