<?php

use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

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
