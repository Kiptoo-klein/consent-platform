<?php

use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        PlatformRoleSeeder::class,
        SubscriptionPlanSeeder::class,
    ]);

    $this->post('/register', [
        'organization_name' => 'Seat Limit Clinic',
        'name' => 'Organization Administrator',
        'email' => 'seat-admin@example.com',
        'password' => 'StrongPass1!',
        'password_confirmation' => 'StrongPass1!',
    ]);

    $this->organization = Organization::query()
        ->where('name', 'Seat Limit Clinic')
        ->firstOrFail();

    $this->subscription = $this->organization
        ->subscription()
        ->with('plan')
        ->firstOrFail();

    $this->subscription->plan->update([
        'max_users' => 2,
    ]);

    $this->staffRole = Role::query()
        ->where(
            'organization_id',
            $this->organization->id
        )
        ->where('guard_name', 'web')
        ->where('name', 'Staff')
        ->firstOrFail();

    $superAdminRole = PlatformRole::query()
        ->where('slug', 'super-admin')
        ->firstOrFail();

    $this->platformAdmin = User::factory()->create([
        'organization_id' => null,
        'platform_role_id' => $superAdminRole->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->platformAdmin);
});

test('disabled users count toward the total user seat limit', function () {
    User::factory()->create([
        'organization_id' => $this->organization->id,
        'is_active' => false,
    ]);

    $response = $this->post(
        route(
            'platform.organizations.users.store',
            $this->organization
        ),
        [
            'name' => 'Third Organization User',
            'email' => 'third-user@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'role_id' => $this->staffRole->id,
        ]
    );

    $response->assertSessionHasErrors('subscription');

    $this->assertDatabaseMissing('users', [
        'email' => 'third-user@example.com',
    ]);
});

test('archived users do not count toward the total user seat limit', function () {
    $archivedUser = User::factory()->create([
        'organization_id' => $this->organization->id,
        'is_active' => false,
    ]);

    $archivedUser->delete();

    $response = $this->post(
        route(
            'platform.organizations.users.store',
            $this->organization
        ),
        [
            'name' => 'Replacement Organization User',
            'email' => 'replacement-user@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'role_id' => $this->staffRole->id,
        ]
    );

    $response->assertRedirect(
        route(
            'platform.organizations.users.index',
            $this->organization
        )
    );

    $response->assertSessionHas(
        'success',
        'Organization user created successfully.'
    );

    $this->assertDatabaseHas('users', [
        'organization_id' => $this->organization->id,
        'email' => 'replacement-user@example.com',
        'deleted_at' => null,
    ]);
});

test(
    'restoring an archived user is blocked when all seats are occupied',
    function () {
        User::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => false,
        ]);

        $archivedUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => false,
        ]);

        $archivedUser->delete();

        $this->assertSame(
            2,
            $this->organization->users()->count()
        );

        $response = $this->patch(
            route(
                'platform.organizations.users.restore',
                [$this->organization, $archivedUser]
            )
        );

        $response->assertSessionHasErrors('subscription');

        $restoredState = User::withTrashed()
            ->findOrFail($archivedUser->id);

        $this->assertTrue($restoredState->trashed());
        $this->assertFalse($restoredState->is_active);
    }
);

test(
    'restoring succeeds after another user is archived',
    function () {
        $occupiedUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => false,
        ]);

        $archivedUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => false,
        ]);

        $archivedUser->delete();

        $this->assertSame(
            2,
            $this->organization->users()->count()
        );

        $occupiedUser->delete();

        $this->assertSame(
            1,
            $this->organization->users()->count()
        );

        $response = $this->patch(
            route(
                'platform.organizations.users.restore',
                [$this->organization, $archivedUser]
            )
        );

        $response->assertRedirect(
            route(
                'platform.organizations.users.index',
                $this->organization
            )
        );

        $response->assertSessionHas(
            'success',
            'Organization user restored successfully. '
            .'The account remains disabled until you enable it.'
        );

        $restoredState = User::withTrashed()
            ->findOrFail($archivedUser->id);

        $this->assertFalse($restoredState->trashed());
        $this->assertFalse($restoredState->is_active);
    }
);

test(
    'creating a user is blocked when the staff role limit is reached',
    function () {
        $this->subscription->plan->update([
            'max_users' => 10,
            'max_staff' => 1,
        ]);

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($this->organization->id);

        $existingStaff = User::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
        ]);

        $existingStaff->assignRole($this->staffRole);

        $response = $this->post(
            route(
                'platform.organizations.users.store',
                $this->organization
            ),
            [
                'name' => 'Second Staff Member',
                'email' => 'second-staff@example.com',
                'password' => 'StrongPass1!',
                'password_confirmation' => 'StrongPass1!',
                'role_id' => $this->staffRole->id,
            ]
        );

        $response->assertSessionHasErrors('subscription');

        $this->assertDatabaseMissing('users', [
            'email' => 'second-staff@example.com',
        ]);
    }
);

test(
    'creating a user is blocked when the consent manager role limit is reached',
    function () {
        $this->subscription->plan->update([
            'max_users' => 10,
            'max_consent_managers' => 1,
        ]);

        $consentManagerRole = Role::query()
            ->where(
                'organization_id',
                $this->organization->id
            )
            ->where('guard_name', 'web')
            ->where('name', 'Consent Manager')
            ->firstOrFail();

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($this->organization->id);

        $existingManager = User::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => false,
        ]);

        $existingManager->assignRole($consentManagerRole);

        $response = $this->post(
            route(
                'platform.organizations.users.store',
                $this->organization
            ),
            [
                'name' => 'Second Consent Manager',
                'email' => 'second-manager@example.com',
                'password' => 'StrongPass1!',
                'password_confirmation' => 'StrongPass1!',
                'role_id' => $consentManagerRole->id,
            ]
        );

        $response->assertSessionHasErrors('subscription');

        $this->assertDatabaseMissing('users', [
            'email' => 'second-manager@example.com',
        ]);
    }
);
