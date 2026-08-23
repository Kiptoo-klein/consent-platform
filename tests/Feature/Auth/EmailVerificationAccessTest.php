<?php

use App\Models\Organization;
use App\Models\User;

test('unverified organization user is blocked from organization workspace', function () {
    $organization = Organization::query()->forceCreate([
        'name' => 'Verification Gate Organization',
        'slug' => 'verification-gate-organization',
    ]);

    $user = User::factory()
        ->unverified()
        ->create([
            'organization_id' => $organization->id,
            'platform_role_id' => null,
            'is_active' => true,
        ]);

    foreach ([
        route('dashboard'),
        route('organization-settings.index'),
        route('consent-templates.index'),
    ] as $url) {
        $this
            ->actingAs($user)
            ->get($url)
            ->assertRedirect(
                route('verification.notice')
            );
    }

    expect(
        $user->fresh()->hasVerifiedEmail()
    )->toBeFalse();
});

test('unverified organization user can open verification screen', function () {
    $organization = Organization::query()->forceCreate([
        'name' => 'Pending Verification Organization',
        'slug' => 'pending-verification-organization',
    ]);

    $user = User::factory()
        ->unverified()
        ->create([
            'organization_id' => $organization->id,
            'platform_role_id' => null,
            'is_active' => true,
        ]);

    $this
        ->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk();
});
