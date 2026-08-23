<?php

use App\Models\PlatformRole;
use App\Models\User;
use App\Notifications\AccountRestorationRequested;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        PlatformRoleSeeder::class,
        SubscriptionPlanSeeder::class,
    ]);

    /*
     * Registration gives us the same Organization Admin setup used
     * by the real application.
     */
    $this->post('/register', [
        'organization_name' =>
            'Restoration Test Clinic',

        'name' =>
            'Restoration Administrator',

        'email' =>
            'restore-admin@example.com',

        'password' =>
            'StrongPass1!',

        'password_confirmation' =>
            'StrongPass1!',
    ]);

    $this->administrator =
        User::query()
            ->where(
                'email',
                'restore-admin@example.com'
            )
            ->firstOrFail();

    $this->administrator
        ->forceFill([
            'email_verified_at' => now(),
        ])
        ->save();

    $this->organization =
        $this->administrator
            ->organization()
            ->firstOrFail();

    app(PermissionRegistrar::class)
        ->setPermissionsTeamId(
            $this->organization->id
        );

    $staffRole =
        Role::query()
            ->where(
                'organization_id',
                $this->organization->id
            )
            ->where(
                'guard_name',
                'web'
            )
            ->where(
                'name',
                'Staff'
            )
            ->firstOrFail();

    $this->staff =
        User::factory()->create([
            'organization_id' =>
                $this->organization->id,

            'platform_role_id' =>
                null,

            'name' =>
                'Restoration Staff',

            'email' =>
                'restore-staff@example.com',

            'password' =>
                'password',

            'is_active' =>
                true,

            'email_verified_at' =>
                now(),
        ]);

    $this->staff->assignRole(
        $staffRole
    );
});

test(
    'correct password exposes restoration for archived account',
    function () {
        $this->staff
            ->forceFill([
                'is_active' => false,
            ])
            ->save();

        $this->staff->delete();

        $this->post('/logout');

        $response =
            $this->post(
                route('login'),
                [
                    'email' =>
                        $this->staff->email,

                    'password' =>
                        'password',
                ]
            );

        $response
            ->assertSessionHasErrors(
                'email'
            )
            ->assertSessionHas(
                'account_restoration',
                function ($context): bool {
                    return
                        is_array($context)
                        && $context['type'] === 'user'
                        && $context['user_id']
                            === $this->staff->id;
                }
            );

        $this->assertGuest();

        $this
            ->get(
                route('login')
            )
            ->assertOk()
            ->assertSeeText(
                'Account restoration available'
            )
            ->assertSeeText(
                'Request account restoration'
            );
    }
);

test(
    'wrong password does not reveal archived account',
    function () {
        $this->staff
            ->forceFill([
                'is_active' => false,
            ])
            ->save();

        $this->staff->delete();

        $this->post('/logout');

        $response =
            $this->post(
                route('login'),
                [
                    'email' =>
                        $this->staff->email,

                    'password' =>
                        'wrong-password',
                ]
            );

        $response
            ->assertSessionHasErrors([
                'email' =>
                    trans('auth.failed'),
            ])
            ->assertSessionMissing(
                'account_restoration'
            );

        $this
            ->get(
                route('login')
            )
            ->assertDontSeeText(
                'Request account restoration'
            );

        $this->assertGuest();
    }
);

test(
    'restoration request without verified login context is rejected',
    function () {
        $this->post('/logout');

        $this
            ->post(
                route(
                    'account-restoration.request'
                )
            )
            ->assertNotFound();

        Notification::fake();

        Notification::assertNothingSent();
    }
);

test(
    'archived account request goes to organization administrator',
    function () {
        Notification::fake();

        $this->staff
            ->forceFill([
                'is_active' => false,
            ])
            ->save();

        $this->staff->delete();

        $this->post('/logout');

        $this->post(
            route('login'),
            [
                'email' =>
                    $this->staff->email,

                'password' =>
                    'password',
            ]
        );

        /*
         * Simulate the guest restoration request beginning without a
         * previously established Spatie organization context.
         */
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(null);

        $response =
            $this->post(
                route(
                    'account-restoration.request'
                )
            );

        $response
            ->assertRedirect(
                route('login')
            )
            ->assertSessionHas(
                'status',
                'Your restoration request has been sent to your '
                .'Organization Administrator.'
            )
            ->assertSessionMissing(
                'account_restoration'
            );

        Notification::assertSentTo(
            $this->administrator,
            AccountRestorationRequested::class,
            function (
                AccountRestorationRequested $notification
            ): bool {
                return
                    $notification->requestType
                        === 'user'
                    && $notification->requesterName
                        === $this->staff->name
                    && $notification->requesterEmail
                        === $this->staff->email
                    && $notification->organizationName
                        === $this->organization->name
                    && $notification->requestedAt !== ''
                    && str_contains(
                        $notification->reviewUrl,
                        '/archived'
                    );
            }
        );
    }
);

test(
    'organization admin restores and enables requested account in one action',
    function () {
        Notification::fake();

        $this->staff
            ->forceFill([
                'is_active' => false,
            ])
            ->save();

        $this->staff->delete();

        $this->post('/logout');

        $this->post(
            route('login'),
            [
                'email' =>
                    $this->staff->email,

                'password' =>
                    'password',
            ]
        );

        $this->post(
            route(
                'account-restoration.request'
            )
        );

        $this
            ->actingAs(
                $this->administrator
            )
            ->patch(
                route(
                    'organization-users.restore',
                    [
                        $this->organization,
                        $this->staff,
                    ]
                )
            )
            ->assertRedirect(
                route(
                    'organization-users.index',
                    $this->organization
                )
            )
            ->assertSessionHas(
                'success',
                'Organization user restored and enabled successfully.'
            );

        $restored =
            User::withTrashed()
                ->findOrFail(
                    $this->staff->id
                );

        expect(
            $restored->trashed()
        )->toBeFalse();

        expect(
            $restored->is_active
        )->toBeTrue();

        $this->post('/logout');

        $this
            ->post(
                route('login'),
                [
                    'email' =>
                        $this->staff->email,

                    'password' =>
                        'password',
                ]
            )
            ->assertRedirect(
                route(
                    'dashboard',
                    absolute: false
                )
            );

        $this->assertAuthenticatedAs(
            $restored
        );
    }
);

test(
    'archived organization request goes only to platform super admin',
    function () {
        Notification::fake();

        $superAdminRole =
            PlatformRole::query()
                ->where(
                    'slug',
                    'super-admin'
                )
                ->firstOrFail();

        $superAdmin =
            User::factory()->create([
                'organization_id' =>
                    null,

                'platform_role_id' =>
                    $superAdminRole->id,

                'name' =>
                    'Platform Super Admin',

                'email' =>
                    'platform-super-admin@example.com',

                'is_active' =>
                    true,

                'email_verified_at' =>
                    now(),
            ]);

        $this->organization
            ->forceFill([
                'archived_at' => now(),
            ])
            ->save();

        $this->post('/logout');

        $response =
            $this->post(
                route('login'),
                [
                    'email' =>
                        $this->staff->email,

                    'password' =>
                        'password',
                ]
            );

        $response
            ->assertRedirect(
                route('login')
            )
            ->assertSessionHas(
                'account_restoration',
                function ($context): bool {
                    return
                        is_array($context)
                        && $context['type']
                            === 'organization'
                        && $context['user_id']
                            === $this->staff->id;
                }
            );

        $this
            ->get(
                route('login')
            )
            ->assertSeeText(
                'Request organization restoration'
            );

        $this
            ->post(
                route(
                    'account-restoration.request'
                )
            )
            ->assertRedirect(
                route('login')
            )
            ->assertSessionHas(
                'status',
                'Your organization restoration request has been sent '
                .'to the Platform Super Admin.'
            );

        Notification::assertSentTo(
            $superAdmin,
            AccountRestorationRequested::class,
            function (
                AccountRestorationRequested $notification
            ): bool {
                return
                    $notification->requestType
                        === 'organization'
                    && $notification->requesterEmail
                        === $this->staff->email
                    && $notification->organizationName
                        === $this->organization->name
                    && $notification->requestedAt !== ''
                    && str_contains(
                        $notification->reviewUrl,
                        '/platform/organizations/'
                    );
            }
        );

        Notification::assertNotSentTo(
            $this->administrator,
            AccountRestorationRequested::class
        );
    }
);

test(
    'repeated restoration request is rate limited',
    function () {
        Notification::fake();

        $this->staff
            ->forceFill([
                'is_active' => false,
            ])
            ->save();

        $this->staff->delete();

        $this->post('/logout');

        /*
         * First verified request.
         */
        $this->post(
            route('login'),
            [
                'email' =>
                    $this->staff->email,

                'password' =>
                    'password',
            ]
        );

        $this->post(
            route(
                'account-restoration.request'
            )
        );

        /*
         * Re-verify the credentials so a fresh legitimate restoration
         * context exists, then attempt another request immediately.
         */
        $this->post(
            route('login'),
            [
                'email' =>
                    $this->staff->email,

                'password' =>
                    'password',
            ]
        );

        $response =
            $this->post(
                route(
                    'account-restoration.request'
                )
            );

        $response
            ->assertSessionHas(
                'status',
                function ($status): bool {
                    return str_contains(
                        $status,
                        'A restoration request was already sent recently.'
                    );
                }
            );

        expect(
            Notification::sent(
                $this->administrator,
                AccountRestorationRequested::class
            )
        )->toHaveCount(1);
    }
);


test(
    'expired restoration verification cannot be used',
    function () {
        Notification::fake();

        $this->post('/logout');

        $response =
            $this
                ->withSession([
                    'account_restoration' => [
                        'type' =>
                            'organization',

                        'user_id' =>
                            $this->staff->id,

                        'expires_at' =>
                            now()
                                ->subMinute()
                                ->timestamp,
                    ],
                ])
                ->post(
                    route(
                        'account-restoration.request'
                    )
                );

        $response
            ->assertRedirect(
                route('login')
            )
            ->assertSessionHas(
                'status',
                'Your restoration verification has expired. '
                .'Sign in again to request restoration.'
            )
            ->assertSessionMissing(
                'account_restoration'
            );

        Notification::assertNothingSent();
    }
);

test(
    'organization archive dominates an individually archived account',
    function () {
        Notification::fake();

        $superAdminRole =
            PlatformRole::query()
                ->where(
                    'slug',
                    'super-admin'
                )
                ->firstOrFail();

        $superAdmin =
            User::factory()->create([
                'organization_id' =>
                    null,

                'platform_role_id' =>
                    $superAdminRole->id,

                'name' =>
                    'Archive Recovery Super Admin',

                'email' =>
                    'archive-recovery-super@example.com',

                'is_active' =>
                    true,

                'email_verified_at' =>
                    now(),
            ]);

        $this->staff
            ->forceFill([
                'is_active' => false,
            ])
            ->save();

        $this->staff->delete();

        $this->organization
            ->forceFill([
                'archived_at' => now(),
            ])
            ->save();

        $this->post('/logout');

        $login =
            $this->post(
                route('login'),
                [
                    'email' =>
                        $this->staff->email,

                    'password' =>
                        'password',
                ]
            );

        $login
            ->assertSessionHas(
                'account_restoration',
                function ($context): bool {
                    return
                        is_array($context)
                        && $context['type']
                            === 'organization'
                        && $context['user_id']
                            === $this->staff->id
                        && ($context['expires_at'] ?? 0)
                            > now()->timestamp;
                }
            );

        $this
            ->get(
                route('login')
            )
            ->assertSeeText(
                'Request organization restoration'
            )
            ->assertDontSeeText(
                'Request account restoration'
            );

        $this
            ->post(
                route(
                    'account-restoration.request'
                )
            )
            ->assertRedirect(
                route('login')
            )
            ->assertSessionHas(
                'status',
                'Your organization restoration request has been sent '
                .'to the Platform Super Admin.'
            );

        Notification::assertSentTo(
            $superAdmin,
            AccountRestorationRequested::class,
            fn (
                AccountRestorationRequested $notification
            ): bool =>
                $notification->requestType
                    === 'organization'
                && $notification->requesterEmail
                    === $this->staff->email
        );
    }
);

test(
    'fresh failed login clears an older restoration verification',
    function () {
        $this->post('/logout');

        $response =
            $this
                ->withSession([
                    'account_restoration' => [
                        'type' =>
                            'organization',

                        'user_id' =>
                            $this->staff->id,

                        'expires_at' =>
                            now()
                                ->addMinutes(10)
                                ->timestamp,
                    ],
                ])
                ->post(
                    route('login'),
                    [
                        'email' =>
                            $this->staff->email,

                        'password' =>
                            'wrong-password',
                    ]
                );

        $response
            ->assertSessionHasErrors(
                'email'
            )
            ->assertSessionMissing(
                'account_restoration'
            );

        $this->assertGuest();
    }
);
