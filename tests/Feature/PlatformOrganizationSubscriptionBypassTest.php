<?php

use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        PlatformRoleSeeder::class,
        SubscriptionPlanSeeder::class,
    ]);

    $this->post('/register', [
        'organization_name' => 'Bypass Test Clinic',
        'name' => 'Organization Administrator',
        'email' => 'organization-admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->organization = Organization::query()
        ->where('name', 'Bypass Test Clinic')
        ->firstOrFail();

    $this->subscription = $this->organization
        ->subscription()
        ->with('plan')
        ->firstOrFail();

    $this->originalPlanId =
        $this->subscription->subscription_plan_id;

    $this->originalUserLimit =
        $this->subscription->plan->max_users;

    $this->originalKioskLimit =
        $this->subscription->plan->max_active_kiosks;

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

test('platform admin bypass approval requires a reason', function () {
    $response = $this->patch(
        "/platform/organizations/{$this->organization->id}/subscription-bypass",
        []
    );

    $response->assertSessionHasErrors('reason');

    expect(
        $this->subscription->fresh()->hasPlatformBypass()
    )->toBeFalse();
});

test('platform admin can approve an organization payment bypass', function () {
    $reason = 'Approved for a supported public-health programme.';

    $response = $this->patch(
        "/platform/organizations/{$this->organization->id}/subscription-bypass",
        [
            'reason' => $reason,
        ]
    );

    $response->assertRedirect(
        route(
            'platform.organizations.show',
            $this->organization
        )
    );

    $subscription = $this->subscription->fresh();

    expect($subscription->bypass_approved_at)
        ->not->toBeNull();

    expect($subscription->bypass_approved_by_user_id)
        ->toBe($this->platformAdmin->id);

    expect($subscription->bypass_reason)
        ->toBe($reason);

    expect($subscription->hasPlatformBypass())
        ->toBeTrue();

    expect($subscription->allowsOrganizationAccess())
        ->toBeTrue();
});

test('platform admin can revoke a bypass without changing plan limits', function () {
    $this->subscription->update([
        'bypass_approved_at' => now(),
        'bypass_approved_by_user_id' =>
            $this->platformAdmin->id,
        'bypass_reason' => 'Temporary approval.',
    ]);

    $response = $this->delete(
        "/platform/organizations/{$this->organization->id}/subscription-bypass"
    );

    $response->assertRedirect(
        route(
            'platform.organizations.show',
            $this->organization
        )
    );

    $subscription = $this->subscription
        ->fresh()
        ->load('plan');

    expect($subscription->bypass_approved_at)
        ->toBeNull();

    expect($subscription->bypass_approved_by_user_id)
        ->toBeNull();

    expect($subscription->bypass_reason)
        ->toBeNull();

    expect($subscription->subscription_plan_id)
        ->toBe($this->originalPlanId);

    expect($subscription->plan->max_users)
        ->toBe($this->originalUserLimit);

    expect($subscription->plan->max_active_kiosks)
        ->toBe($this->originalKioskLimit);

    expect($subscription->allowsOrganizationAccess())
        ->toBeFalse();
});

test('platform admin can view organization subscription controls', function () {
    $response = $this->get(
        route(
            'platform.organizations.show',
            $this->organization
        )
    );

    $response->assertOk();

    $response->assertSeeText('Subscription Access');
    $response->assertSeeText('Basic');
    $response->assertSeeText('Unpaid');
    $response->assertSeeText('Approve Payment Bypass');
});
