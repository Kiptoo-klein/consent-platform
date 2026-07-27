<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $organizationData = [
        'name' => 'Authentication Test Organization',
    ];

    if (Schema::hasColumn('organizations', 'slug')) {
        $organizationData['slug'] =
            'authentication-test-organization';
    }

    if (Schema::hasColumn('organizations', 'is_active')) {
        $organizationData['is_active'] = true;
    }

    $organization = Organization::query()
        ->forceCreate($organizationData);

    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
