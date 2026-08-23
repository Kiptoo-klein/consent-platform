<?php

use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\SigningStation;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        PlatformRoleSeeder::class,
        SubscriptionPlanSeeder::class,
    ]);

    /*
     * Registration gives us a real Organization Admin with the
     * same roles and organization setup used in production.
     */
    $this->post('/register', [
        'organization_name' =>
            'Archive Test Clinic',

        'name' =>
            'Archive Test Administrator',

        'email' =>
            'archive-admin@example.com',

        'password' =>
            'StrongPass1!',

        'password_confirmation' =>
            'StrongPass1!',
    ]);

    $this->administrator =
        User::query()
            ->where(
                'email',
                'archive-admin@example.com'
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

    $this->staffRole =
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
                'Archive Test Staff',

            'email' =>
                'archive-staff@example.com',

            'password' =>
                'password',

            'is_active' =>
                true,

            'email_verified_at' =>
                now(),
        ]);

    $this->staff->assignRole(
        $this->staffRole
    );
});

test(
    'normal organization user archives only their own account',
    function () {
        $response =
            $this
                ->actingAs($this->staff)
                ->delete(
                    route('profile.destroy'),
                    [
                        'password' =>
                            'password',
                    ]
                );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();

        $this->assertSoftDeleted(
            'users',
            [
                'id' =>
                    $this->staff->id,
            ]
        );

        expect(
            User::query()
                ->find($this->staff->id)
        )->toBeNull();

        $archivedStaff =
            User::withTrashed()
                ->findOrFail(
                    $this->staff->id
                );

        expect(
            $archivedStaff->is_active
        )->toBeFalse();

        expect(
            $archivedStaff->trashed()
        )->toBeTrue();

        expect(
            $this->organization
                ->fresh()
                ->archived_at
        )->toBeNull();

        expect(
            $this->administrator
                ->fresh()
                ->is_active
        )->toBeTrue();
    }
);

test(
    'non admin billing owner cannot archive their own account',
    function () {
        $this->organization
            ->subscription()
            ->update([
                'billing_owner_user_id' =>
                    $this->staff->id,
            ]);

        $response =
            $this
                ->actingAs($this->staff)
                ->from(
                    route('profile.edit')
                )
                ->delete(
                    route('profile.destroy'),
                    [
                        'password' =>
                            'password',
                    ]
                );

        $response
            ->assertRedirect(
                route('profile.edit')
            )
            ->assertSessionHasErrorsIn(
                'userDeletion',
                'archive'
            );

        expect(
            User::query()
                ->find($this->staff->id)
        )->not->toBeNull();

        expect(
            $this->staff
                ->fresh()
                ->is_active
        )->toBeTrue();

        expect(
            $this->organization
                ->fresh()
                ->archived_at
        )->toBeNull();
    }
);

test(
    'organization admin profile clearly warns about organization archive',
    function () {
        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route('profile.edit')
            )
            ->assertOk()
            ->assertSeeText(
                'Archive Organization'
            )
            ->assertSeeText(
                'This affects every user in Archive Test Clinic.'
            )
            ->assertSeeText(
                'All organization users will lose access.'
            )
            ->assertSeeText(
                'Only a Platform Super Admin will be able to restore the organization.'
            )
            ->assertSeeText(
                'Type the organization name to confirm'
            );
    }
);

test(
    'organization admin must enter exact organization name before archiving',
    function () {
        $response =
            $this
                ->actingAs(
                    $this->administrator
                )
                ->from(
                    route('profile.edit')
                )
                ->delete(
                    route('profile.destroy'),
                    [
                        'password' =>
                            'StrongPass1!',

                        'organization_name' =>
                            'Wrong Clinic',
                    ]
                );

        $response
            ->assertRedirect(
                route('profile.edit')
            )
            ->assertSessionHasErrorsIn(
                'userDeletion',
                'organization_name'
            );

        expect(
            $this->organization
                ->fresh()
                ->archived_at
        )->toBeNull();

        $this->assertNotSoftDeleted(
            'users',
            [
                'id' =>
                    $this->administrator->id,
            ]
        );
    }
);

test(
    'organization admin self archive archives organization but preserves user',
    function () {
        $response =
            $this
                ->actingAs(
                    $this->administrator
                )
                ->delete(
                    route('profile.destroy'),
                    [
                        'password' =>
                            'StrongPass1!',

                        'organization_name' =>
                            $this->organization
                                ->name,
                    ]
                );

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();

        expect(
            $this->organization
                ->fresh()
                ->archived_at
        )->not->toBeNull();

        $administrator =
            User::query()
                ->findOrFail(
                    $this->administrator->id
                );

        expect(
            $administrator->is_active
        )->toBeTrue();

        $this->assertNotSoftDeleted(
            'users',
            [
                'id' =>
                    $this->administrator->id,
            ]
        );

        $this->assertNotSoftDeleted(
            'users',
            [
                'id' =>
                    $this->staff->id,
            ]
        );
    }
);

test(
    'already authenticated organization user loses access after organization archive',
    function () {
        $this->organization
            ->forceFill([
                'archived_at' =>
                    now(),
            ])
            ->save();

        $response =
            $this
                ->actingAs(
                    $this->staff
                )
                ->get(
                    route('profile.edit')
                );

        $response
            ->assertRedirect(
                route('login')
            )
            ->assertSessionHasErrors(
                'email'
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

        $this->assertGuest();

        $this
            ->get(
                route('login')
            )
            ->assertOk()
            ->assertSeeText(
                'Request organization restoration'
            );
    }
);

test(
    'password login is rejected for archived organization',
    function () {
        /*
         * Registration in beforeEach authenticates the founder.
         * Log that fixture account out so this request actually reaches
         * the login controller rather than the guest middleware redirect.
         */
        $this
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();

        $this->organization
            ->forceFill([
                'archived_at' =>
                    now(),
            ])
            ->save();

        $response =
            $this
                ->post(
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
            ->assertSessionHasErrors(
                'email'
            );

        $this->assertGuest();
    }
);

test(
    'public signing station is unavailable for archived organization',
    function () {
        $template =
            createArchiveTestPublishedTemplate(
                $this->organization,
                $this->administrator
            );

        $station =
            SigningStation::query()
                ->create([
                    'organization_id' =>
                        $this->organization->id,

                    'consent_template_id' =>
                        $template->id,

                    'created_by' =>
                        $this->administrator->id,

                    'name' =>
                        'Archive Test Station',

                    'station_token' =>
                        Str::random(64),

                    'active' =>
                        true,

                    'require_email' =>
                        false,

                    'require_reference' =>
                        false,

                    'auto_reset_seconds' =>
                        3,

                    'qr_expires_at' =>
                        now()->addHours(24),
                ]);

        $this->organization
            ->forceFill([
                'archived_at' =>
                    now(),
            ])
            ->save();

        $this
            ->get(
                route(
                    'public-signing-stations.scan',
                    $station->station_token
                )
            )
            ->assertNotFound();
    }
);

test(
    'individual consent link is unavailable for archived organization',
    function () {
        $template =
            createArchiveTestPublishedTemplate(
                $this->organization,
                $this->administrator
            );

        $accessToken =
            (string) Str::uuid();

        ConsentSession::query()
            ->create([
                'organization_id' =>
                    $this->organization->id,

                'consent_template_id' =>
                    $template->id,

                'consent_template_version_id' =>
                    $template
                        ->active_version_id,

                'created_by' =>
                    $this->administrator->id,

                'signer_name' =>
                    'Archive Test Signer',

                'signer_email' =>
                    'signer@example.com',

                'access_token' =>
                    $accessToken,

                'status' =>
                    ConsentSession::
                        STATUS_PENDING,
            ]);

        $this->organization
            ->forceFill([
                'archived_at' =>
                    now(),
            ])
            ->save();

        $this
            ->get(
                route(
                    'public-consent.show',
                    $accessToken
                )
            )
            ->assertNotFound();
    }
);

test(
    'obsolete qr token still returns gone response',
    function () {
        $this
            ->get(
                route(
                    'public-signing-stations.scan',
                    Str::random(64)
                )
            )
            ->assertStatus(410)
            ->assertSeeText(
                'This QR code is no longer available'
            );
    }
);


test(
    'platform super admin can restore archived organization',
    function () {
        $this->organization
            ->forceFill([
                'archived_at' => now()->subHour(),
            ])
            ->save();

        $superAdminRole =
            PlatformRole::query()
                ->where('slug', 'super-admin')
                ->firstOrFail();

        $platformAdmin =
            User::factory()->create([
                'organization_id' => null,
                'platform_role_id' =>
                    $superAdminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

        $response =
            $this
                ->actingAs($platformAdmin)
                ->patch(
                    route(
                        'platform.organizations.restore',
                        $this->organization
                    )
                );

        $response
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertSessionHas(
                'success',
                'Organization restored successfully.'
            );

        expect(
            $this->organization
                ->fresh()
                ->archived_at
        )->toBeNull();

        /*
         * Restoring an organization clears only the organization lock.
         * Existing users are preserved unchanged.
         */
        expect(
            $this->administrator
                ->fresh()
                ->is_active
        )->toBeTrue();

        expect(
            $this->staff
                ->fresh()
                ->is_active
        )->toBeTrue();

        $this->assertNotSoftDeleted(
            'users',
            [
                'id' => $this->administrator->id,
            ]
        );

        $this->assertNotSoftDeleted(
            'users',
            [
                'id' => $this->staff->id,
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'organization_id' =>
                    $this->organization->id,
                'user_id' =>
                    $platformAdmin->id,
                'action' =>
                    'organization.restored',
            ]
        );
    }
);

test(
    'non super admin platform role cannot restore organization',
    function () {
        $this->organization
            ->forceFill([
                'archived_at' => now(),
            ])
            ->save();

        $supportRole =
            PlatformRole::query()
                ->where('slug', 'support')
                ->firstOrFail();

        $supportUser =
            User::factory()->create([
                'organization_id' => null,
                'platform_role_id' =>
                    $supportRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

        $this
            ->actingAs($supportUser)
            ->patch(
                route(
                    'platform.organizations.restore',
                    $this->organization
                )
            )
            ->assertForbidden();

        expect(
            $this->organization
                ->fresh()
                ->archived_at
        )->not->toBeNull();
    }
);

test(
    'platform organization page shows restore only to super admin',
    function () {
        $this->organization
            ->forceFill([
                'archived_at' => now(),
            ])
            ->save();

        $superAdminRole =
            PlatformRole::query()
                ->where('slug', 'super-admin')
                ->firstOrFail();

        $supportRole =
            PlatformRole::query()
                ->where('slug', 'support')
                ->firstOrFail();

        $platformAdmin =
            User::factory()->create([
                'organization_id' => null,
                'platform_role_id' =>
                    $superAdminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

        $supportUser =
            User::factory()->create([
                'organization_id' => null,
                'platform_role_id' =>
                    $supportRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

        $this
            ->actingAs($platformAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertOk()
            ->assertSeeText('Archived')
            ->assertSeeText(
                'Organization access is currently blocked'
            )
            ->assertSeeText(
                'Restore Organization'
            );

        $this
            ->actingAs($supportUser)
            ->get(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertOk()
            ->assertSeeText('Archived')
            ->assertDontSeeText(
                'Restore Organization'
            )
            ->assertSeeText(
                'Only a Platform Super Admin can restore this organization.'
            );
    }
);

test(
    'restored organization users can authenticate again',
    function () {
        $this->organization
            ->forceFill([
                'archived_at' => now(),
            ])
            ->save();

        $superAdminRole =
            PlatformRole::query()
                ->where('slug', 'super-admin')
                ->firstOrFail();

        $platformAdmin =
            User::factory()->create([
                'organization_id' => null,
                'platform_role_id' =>
                    $superAdminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

        $this
            ->actingAs($platformAdmin)
            ->patch(
                route(
                    'platform.organizations.restore',
                    $this->organization
                )
            )
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            );

        $this->post('/logout');

        $this->assertGuest();

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
            $this->staff
        );
    }
);

/**
 * Create a real published consent template for public archive tests.
 */
function createArchiveTestPublishedTemplate(
    Organization $organization,
    User $administrator
): ConsentTemplate {
    $template =
        ConsentTemplate::query()
            ->create([
                'organization_id' =>
                    $organization->id,

                'title' =>
                    'Archive Test Consent',

                'description' =>
                    'Consent used by organization archive tests.',

                'category' =>
                    'Testing',

                'usage_type' =>
                    ConsentTemplate::
                        USAGE_SIGNING_STATION,

                'template_schema' =>
                    [
                        'sections' => [],
                    ],

                'active_version_id' =>
                    null,

                'has_unpublished_changes' =>
                    false,

                'status' =>
                    'draft',
            ]);

    $version =
        $template
            ->versions()
            ->create([
                'version_number' =>
                    1,

                'title' =>
                    $template->title,

                'description' =>
                    $template->description,

                'template_schema' =>
                    $template
                        ->template_schema,

                'published_at' =>
                    now(),

                'published_by' =>
                    $administrator->id,
            ]);

    $template->update([
        'active_version_id' =>
            $version->id,

        'has_unpublished_changes' =>
            false,

        'status' =>
            'published',
    ]);

    return $template->refresh();
}
