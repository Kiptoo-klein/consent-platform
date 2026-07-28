<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new organizations can register', function () {
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

    $response->assertRedirect(
        route('dashboard', absolute: false)
    );
});
