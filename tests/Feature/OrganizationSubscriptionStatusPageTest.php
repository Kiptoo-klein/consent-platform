<?php

use Database\Seeders\SubscriptionPlanSeeder;

test('organization users can view their subscription status', function () {
    $this->seed(SubscriptionPlanSeeder::class);

    $this->post('/register', [
        'organization_name' => 'Subscription Test Clinic',
        'name' => 'Subscription Administrator',
        'email' => 'subscription-admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $response = $this->get('/organization/subscription');

    $response->assertOk();

    $response->assertSee('Subscription Status');
    $response->assertSee('Basic');
    $response->assertSee('Unpaid');
    $response->assertSee('Payment required');
});
