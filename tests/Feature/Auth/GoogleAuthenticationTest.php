<?php

use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationWelcome;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function googleAuthenticationFakeUser(
    array $overrides = []
): SocialiteUser {
    return SocialiteUser::fake(
        array_merge(
            [
                'id' =>
                    'google-user-123',

                'name' =>
                    'Google Test User',

                'email' =>
                    'google-user@example.com',

                'email_verified' =>
                    true,
            ],
            $overrides
        )
    );
}

function googleAuthenticationOrganization(
    string $name = 'Google Login Organization'
): Organization {
    return Organization::query()
        ->forceCreate([
            'name' =>
                $name,

            'slug' =>
                str($name)
                    ->slug()
                    ->append('-test')
                    ->toString(),

            'email' =>
                'organization@example.com',
        ]);
}

test('google registration creates verified founder and uses google email as organization email', function () {
    Notification::fake();

    $this->seed(
        SubscriptionPlanSeeder::class
    );

    Socialite::fake(
        'google',
        googleAuthenticationFakeUser([
            'id' =>
                'google-founder-001',

            'name' =>
                'Google Founder',

            'email' =>
                'Google-Founder@Example.com',

            'email_verified' =>
                true,
        ])
    );

    $response =
        $this
            ->withSession([
                'google_auth.intent' =>
                    'register',

                'google_auth.organization_name' =>
                    'Google Registration Clinic',
            ])
            ->get(
                route(
                    'google.callback',
                    absolute: false
                )
            );

    $user =
        User::query()
            ->where(
                'email',
                'google-founder@example.com'
            )
            ->firstOrFail();

    $organization =
        Organization::query()
            ->findOrFail(
                $user->organization_id
            );

    expect($user->google_id)
        ->toBe('google-founder-001');

    expect($user->hasVerifiedEmail())
        ->toBeTrue();

    expect($organization->name)
        ->toBe('Google Registration Clinic');

    /*
     * This is a core product contract:
     * the founder's verified Google email becomes the new
     * organization's initial/default email.
     */
    expect($organization->email)
        ->toBe(
            'google-founder@example.com'
        );

    expect(
        $organization
            ->subscription()
            ->firstOrFail()
            ->isEvaluation()
    )->toBeTrue();

    Notification::assertSentTo(
        $user,
        OrganizationWelcome::class
    );

    Notification::assertNotSentTo(
        $user,
        VerifyEmail::class
    );

    $this->assertAuthenticatedAs(
        $user
    );

    $response->assertRedirect(
        route(
            'dashboard',
            absolute: false
        )
    );
});

test('google registration rejects an unverified google email', function () {
    $this->seed(
        SubscriptionPlanSeeder::class
    );

    Socialite::fake(
        'google',
        googleAuthenticationFakeUser([
            'id' =>
                'google-unverified-register',

            'email' =>
                'unverified-google@example.com',

            'email_verified' =>
                false,
        ])
    );

    $response =
        $this
            ->withSession([
                'google_auth.intent' =>
                    'register',

                'google_auth.organization_name' =>
                    'Rejected Google Organization',
            ])
            ->get(
                route(
                    'google.callback',
                    absolute: false
                )
            );

    $this->assertGuest();

    expect(
        User::query()
            ->where(
                'email',
                'unverified-google@example.com'
            )
            ->exists()
    )->toBeFalse();

    expect(
        Organization::query()
            ->where(
                'name',
                'Rejected Google Organization'
            )
            ->exists()
    )->toBeFalse();

    $response
        ->assertRedirect(
            route(
                'register',
                absolute: false
            )
        )
        ->assertSessionHasErrors(
            'email'
        );
});

test('google login authenticates an already linked verified account', function () {
    $organization =
        googleAuthenticationOrganization();

    $user =
        User::factory()
            ->create([
                'organization_id' =>
                    $organization->id,

                'google_id' =>
                    'google-linked-001',

                'email' =>
                    'linked@example.com',

                'is_active' =>
                    true,
            ]);

    Socialite::fake(
        'google',
        googleAuthenticationFakeUser([
            'id' =>
                'google-linked-001',

            'email' =>
                'linked@example.com',
        ])
    );

    $response =
        $this
            ->withSession([
                'google_auth.intent' =>
                    'login',
            ])
            ->get(
                route(
                    'google.callback',
                    absolute: false
                )
            );

    $this->assertAuthenticatedAs(
        $user
    );

    $response->assertRedirect(
        route(
            'dashboard',
            absolute: false
        )
    );
});

test('google login safely links an existing verified account with the same email', function () {
    $organization =
        googleAuthenticationOrganization(
            'Google Safe Linking Organization'
        );

    $user =
        User::factory()
            ->create([
                'organization_id' =>
                    $organization->id,

                'google_id' =>
                    null,

                'email' =>
                    'safe-link@example.com',

                'is_active' =>
                    true,
            ]);

    Socialite::fake(
        'google',
        googleAuthenticationFakeUser([
            'id' =>
                'google-safe-link-001',

            'email' =>
                'SAFE-LINK@example.com',

            'email_verified' =>
                true,
        ])
    );

    $response =
        $this
            ->withSession([
                'google_auth.intent' =>
                    'login',
            ])
            ->get(
                route(
                    'google.callback',
                    absolute: false
                )
            );

    expect(
        $user->fresh()->google_id
    )->toBe(
        'google-safe-link-001'
    );

    $this->assertAuthenticatedAs(
        $user
    );

    $response->assertRedirect(
        route(
            'dashboard',
            absolute: false
        )
    );
});

test('google login does not bypass setup for an unverified invited account', function () {
    $organization =
        googleAuthenticationOrganization(
            'Pending Google Login Organization'
        );

    $user =
        User::factory()
            ->unverified()
            ->create([
                'organization_id' =>
                    $organization->id,

                'google_id' =>
                    null,

                'email' =>
                    'pending-google@example.com',

                'is_active' =>
                    true,
            ]);

    Socialite::fake(
        'google',
        googleAuthenticationFakeUser([
            'id' =>
                'google-pending-001',

            'email' =>
                'pending-google@example.com',

            'email_verified' =>
                true,
        ])
    );

    $response =
        $this
            ->withSession([
                'google_auth.intent' =>
                    'login',
            ])
            ->get(
                route(
                    'google.callback',
                    absolute: false
                )
            );

    expect(
        $user->fresh()->google_id
    )->toBeNull();

    expect(
        $user->fresh()->hasVerifiedEmail()
    )->toBeFalse();

    $this->assertGuest();

    $response
        ->assertRedirect(
            route(
                'login',
                absolute: false
            )
        )
        ->assertSessionHasErrors(
            'email'
        );
});

test('google login rejects disabled linked accounts', function () {
    $organization =
        googleAuthenticationOrganization(
            'Disabled Google Organization'
        );

    $user =
        User::factory()
            ->create([
                'organization_id' =>
                    $organization->id,

                'google_id' =>
                    'google-disabled-001',

                'email' =>
                    'disabled-google@example.com',

                'is_active' =>
                    false,
            ]);

    Socialite::fake(
        'google',
        googleAuthenticationFakeUser([
            'id' =>
                'google-disabled-001',

            'email' =>
                'disabled-google@example.com',
        ])
    );

    $response =
        $this
            ->withSession([
                'google_auth.intent' =>
                    'login',
            ])
            ->get(
                route(
                    'google.callback',
                    absolute: false
                )
            );

    $this->assertGuest();

    $response
        ->assertRedirect(
            route(
                'login',
                absolute: false
            )
        )
        ->assertSessionHasErrors(
            'email'
        );
});

test('unknown google login redirects to registration without creating an account', function () {
    Socialite::fake(
        'google',
        googleAuthenticationFakeUser([
            'id' =>
                'google-unknown-001',

            'name' =>
                'Unknown Google User',

            'email' =>
                'unknown-google@example.com',

            'email_verified' =>
                true,
        ])
    );

    $response =
        $this
            ->withSession([
                'google_auth.intent' =>
                    'login',
            ])
            ->get(
                route(
                    'google.callback',
                    absolute: false
                )
            );

    $this->assertGuest();

    expect(
        User::query()
            ->where(
                'email',
                'unknown-google@example.com'
            )
            ->exists()
    )->toBeFalse();

    $response
        ->assertRedirect(
            route(
                'register',
                absolute: false
            )
        )
        ->assertSessionHasErrors(
            'email'
        )
        ->assertSessionHasInput(
            'email',
            'unknown-google@example.com'
        );
});

test('google registration never creates a second organization for an existing email', function () {
    $this->seed(
        SubscriptionPlanSeeder::class
    );

    $organization =
        googleAuthenticationOrganization(
            'Existing Account Organization'
        );

    User::factory()
        ->create([
            'organization_id' =>
                $organization->id,

            'email' =>
                'already-exists@example.com',

            'is_active' =>
                true,
        ]);

    $organizationCount =
        Organization::query()->count();

    Socialite::fake(
        'google',
        googleAuthenticationFakeUser([
            'id' =>
                'google-existing-email-001',

            'email' =>
                'already-exists@example.com',

            'email_verified' =>
                true,
        ])
    );

    $response =
        $this
            ->withSession([
                'google_auth.intent' =>
                    'register',

                'google_auth.organization_name' =>
                    'Must Not Be Created',
            ])
            ->get(
                route(
                    'google.callback',
                    absolute: false
                )
            );

    expect(
        Organization::query()->count()
    )->toBe(
        $organizationCount
    );

    expect(
        Organization::query()
            ->where(
                'name',
                'Must Not Be Created'
            )
            ->exists()
    )->toBeFalse();

    $this->assertGuest();

    $response
        ->assertRedirect(
            route(
                'login',
                absolute: false
            )
        )
        ->assertSessionHasErrors(
            'email'
        );
});
