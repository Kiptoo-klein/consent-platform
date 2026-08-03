<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new organizations can register', function () {
    $this->seed(
        \Database\Seeders\SubscriptionPlanSeeder::class
    );

    $response = $this->post('/register', [
        'organization_name' => 'Test Health Centre',
        'name' => 'Test Administrator',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $organization = \App\Models\Organization::query()
        ->where('name', 'Test Health Centre')
        ->firstOrFail();

    $user = \App\Models\User::query()
        ->where('email', 'test@example.com')
        ->firstOrFail();

    expect($user->organization_id)->toBe($organization->id);

    app(
        \Spatie\Permission\PermissionRegistrar::class
    )->setPermissionsTeamId($organization->id);

    expect($user->fresh()->hasRole('Organization Admin'))
        ->toBeTrue();

    $subscription = $organization
        ->subscription()
        ->with('plan')
        ->firstOrFail();

    /*
     * Basic remains an internal relational placeholder only.
     * It has not been selected by the customer.
     */
    expect($subscription->plan->slug)->toBe('basic');
    expect($subscription->requires_plan_selection)->toBeTrue();
    expect($subscription->requiresPlanSelection())->toBeTrue();
    expect($subscription->plan_selected_at)->toBeNull();

    expect($subscription->status)->toBe(
        \App\Enums\OrganizationSubscriptionStatus::TRIALING
    );

    expect($subscription->payment_status)->toBe(
        \App\Enums\SubscriptionPaymentStatus::UNPAID
    );

    expect($subscription->billing_owner_user_id)
        ->toBe($user->id);

    expect($subscription->bypass_approved_at)->toBeNull();
    expect($subscription->bypass_approved_by_user_id)->toBeNull();

    expect($subscription->allowsOrganizationAccess())
        ->toBeFalse();

    $response->assertRedirect(
        route(
            'organization-subscription-plans.index',
            absolute: false
        )
    );
});
