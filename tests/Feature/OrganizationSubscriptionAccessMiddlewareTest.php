<?php

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Organization;
use Database\Seeders\SubscriptionPlanSeeder;

test('unpaid organizations are redirected away from workflows', function () {
    $this->seed(SubscriptionPlanSeeder::class);

    $this->post('/register', [
        'organization_name' => 'Unpaid Clinic',
        'name' => 'Unpaid Administrator',
        'email' => 'unpaid-admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $response = $this->get('/dashboard');

    $response->assertRedirect(
        route('organization-subscription.show')
    );
});

test('paid organizations may access workflows', function () {
    $this->seed(SubscriptionPlanSeeder::class);

    $this->post('/register', [
        'organization_name' => 'Paid Clinic',
        'name' => 'Paid Administrator',
        'email' => 'paid-admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $organization = Organization::query()
        ->where('name', 'Paid Clinic')
        ->firstOrFail();

    $organization->subscription()->update([
        'payment_status' => SubscriptionPaymentStatus::PAID,
    ]);

    $response = $this->get('/dashboard');

    $response->assertOk();
});


test('an unexpired trial may access organization workflows', function () {
    $this->seed(SubscriptionPlanSeeder::class);

    $this->post('/register', [
        'organization_name' => 'Trial Clinic',
        'name' => 'Trial Administrator',
        'email' => 'trial-admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $organization = Organization::query()
        ->where('name', 'Trial Clinic')
        ->firstOrFail();

    $organization->subscription()->update([
        'status' =>
            OrganizationSubscriptionStatus::TRIALING,
        'payment_status' =>
            SubscriptionPaymentStatus::UNPAID,
        'trial_ends_at' => now()->addDay(),
    ]);

    $this->get('/dashboard')->assertOk();
});

test('an expired paid period is redirected from workflows', function () {
    $this->seed(SubscriptionPlanSeeder::class);

    $this->post('/register', [
        'organization_name' => 'Expired Clinic',
        'name' => 'Expired Administrator',
        'email' => 'expired-admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $organization = Organization::query()
        ->where('name', 'Expired Clinic')
        ->firstOrFail();

    $organization->subscription()->update([
        'status' =>
            OrganizationSubscriptionStatus::ACTIVE,
        'payment_status' =>
            SubscriptionPaymentStatus::PAID,
        'current_period_starts_at' => now()->subMonth(),
        'current_period_ends_at' => now()->subMinute(),
    ]);

    $this->get('/dashboard')->assertRedirect(
        route('organization-subscription.show')
    );
});
