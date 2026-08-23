<?php

use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\User;
use App\Notifications\OrganizationUserInvitation;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->seed([
        PlatformRoleSeeder::class,
        SubscriptionPlanSeeder::class,
    ]);

    $this->post('/register', [
        'organization_name' =>
            'Invitation Management Clinic',
        'name' =>
            'Organization Administrator',
        'email' =>
            'invitation-owner@example.com',
        'password' =>
            'StrongPass1!',
        'password_confirmation' =>
            'StrongPass1!',
    ]);

    $this->organization = Organization::query()
        ->where(
            'name',
            'Invitation Management Clinic'
        )
        ->firstOrFail();

    $this->staffRole = Role::query()
        ->where(
            'organization_id',
            $this->organization->id
        )
        ->where('guard_name', 'web')
        ->where('name', 'Staff')
        ->firstOrFail();

    $superAdminRole = PlatformRole::query()
        ->where('slug', 'super-admin')
        ->firstOrFail();

    $this->platformAdmin = User::factory()->create([
        'organization_id' => null,
        'platform_role_id' => $superAdminRole->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->platformAdmin);
});

test('add user form does not ask administrator for password', function () {
    $this
        ->get(
            route(
                'platform.organizations.users.create',
                $this->organization
            )
        )
        ->assertOk()
        ->assertSeeText('Send Invitation')
        ->assertDontSee('name="password"', false)
        ->assertDontSee(
            'name="password_confirmation"',
            false
        );
});

test('creating organization user sends setup invitation', function () {
    $response = $this->post(
        route(
            'platform.organizations.users.store',
            $this->organization
        ),
        [
            'name' => 'Invited Staff',
            'email' => 'invited-staff@example.com',
            'role_id' => $this->staffRole->id,

            /*
             * Crafted password input must be ignored.
             */
            'password' => 'AdminChosenPass1!',
            'password_confirmation' =>
                'AdminChosenPass1!',
        ]
    );

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(
            route(
                'platform.organizations.users.index',
                $this->organization
            )
        );

    $user = User::query()
        ->where(
            'email',
            'invited-staff@example.com'
        )
        ->firstOrFail();

    expect($user->is_active)->toBeTrue();
    expect($user->hasVerifiedEmail())->toBeFalse();

    expect(
        Hash::check(
            'AdminChosenPass1!',
            $user->password
        )
    )->toBeFalse();

    Notification::assertSentTo(
        $user,
        OrganizationUserInvitation::class,
        function (
            OrganizationUserInvitation $notification
        ) use ($user): bool {
            return $notification->organizationName
                    === 'Invitation Management Clinic'
                && $notification->roleName === 'Staff'
                && Password::broker(
                    'organization_invitations'
                )->tokenExists(
                    $user,
                    $notification->token
                );
        }
    );
});

test('disabled pending invitation no longer appears in active count', function () {
    $this->post(
        route(
            'platform.organizations.users.store',
            $this->organization
        ),
        [
            'name' => 'Wrong Email User',
            'email' => 'wrong-email@example.com',
            'role_id' => $this->staffRole->id,
        ]
    )->assertSessionHasNoErrors();

    $user = User::query()
        ->where(
            'email',
            'wrong-email@example.com'
        )
        ->firstOrFail();

    $notification = Notification::sent(
        $user,
        OrganizationUserInvitation::class
    )->first();

    expect($notification)->not->toBeNull();

    $this
        ->get(
            route(
                'platform.organizations.users.index',
                $this->organization
            )
        )
        ->assertOk()
        ->assertSeeText('Pending setup')
        ->assertSee(
            'data-active-user-count="2"',
            false
        );

    $this
        ->patch(
            route(
                'platform.organizations.users.status',
                [$this->organization, $user]
            ),
            [
                'is_active' => false,
            ]
        )
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->is_active)->toBeFalse();

    expect(
        Password::broker(
            'organization_invitations'
        )->tokenExists(
            $user,
            $notification->token
        )
    )->toBeFalse();

    $this
        ->get(
            route(
                'platform.organizations.users.index',
                $this->organization
            )
        )
        ->assertOk()
        ->assertSeeText('Disabled')
        ->assertSee(
            'data-active-user-count="1"',
            false
        );
});

test('re enabling pending user sends fresh invitation', function () {
    $this->post(
        route(
            'platform.organizations.users.store',
            $this->organization
        ),
        [
            'name' => 'Reenabled User',
            'email' => 'reenabled@example.com',
            'role_id' => $this->staffRole->id,
        ]
    )->assertSessionHasNoErrors();

    $user = User::query()
        ->where(
            'email',
            'reenabled@example.com'
        )
        ->firstOrFail();

    $firstNotification = Notification::sent(
        $user,
        OrganizationUserInvitation::class
    )->first();

    $this->patch(
        route(
            'platform.organizations.users.status',
            [$this->organization, $user]
        ),
        [
            'is_active' => false,
        ]
    )->assertSessionHasNoErrors();

    Notification::fake();

    $this->patch(
        route(
            'platform.organizations.users.status',
            [$this->organization, $user]
        ),
        [
            'is_active' => true,
        ]
    )->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->is_active)->toBeTrue();

    Notification::assertSentTo(
        $user,
        OrganizationUserInvitation::class,
        function (
            OrganizationUserInvitation $notification
        ) use (
            $user,
            $firstNotification
        ): bool {
            return $notification->token
                    !== $firstNotification->token
                && Password::broker(
                    'organization_invitations'
                )->tokenExists(
                    $user,
                    $notification->token
                );
        }
    );
});
