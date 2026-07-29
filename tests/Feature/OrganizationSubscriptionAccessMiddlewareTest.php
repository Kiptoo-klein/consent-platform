<?php

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
