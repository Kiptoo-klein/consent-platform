<?php

use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationUserInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

function invitationTestUser(
    bool $active = true
): User {
    $organization = Organization::query()->forceCreate([
        'name' => 'Invitation Test Organization',
        'slug' => 'invitation-test-organization-'.uniqid(),
    ]);

    return User::factory()
        ->unverified()
        ->create([
            'organization_id' => $organization->id,
            'platform_role_id' => null,
            'is_active' => $active,
        ]);
}

test('valid organization invitation can be opened', function () {
    $user = invitationTestUser();

    $token = Password::broker(
        'organization_invitations'
    )->createToken($user);

    $this
        ->get(
            route(
                'organization-invitations.accept',
                [
                    'token' => $token,
                    'email' => $user->email,
                ]
            )
        )
        ->assertOk()
        ->assertSeeText(
            'Complete your account setup'
        )
        ->assertSeeText($user->name)
        ->assertSeeText($user->email);
});

test('invited user chooses own password and verifies email', function () {
    $user = invitationTestUser();

    $token = Password::broker(
        'organization_invitations'
    )->createToken($user);

    $response = $this->post(
        route(
            'organization-invitations.complete',
            ['token' => $token]
        ),
        [
            'email' => $user->email,
            'password' => 'NewInvitePass1!',
            'password_confirmation' =>
                'NewInvitePass1!',
        ]
    );

    $response->assertRedirect(
        route('login')
    );

    $user->refresh();

    expect(
        $user->hasVerifiedEmail()
    )->toBeTrue();

    expect(
        Hash::check(
            'NewInvitePass1!',
            $user->password
        )
    )->toBeTrue();

    expect(
        Password::broker(
            'organization_invitations'
        )->tokenExists(
            $user,
            $token
        )
    )->toBeFalse();
});

test('disabled user cannot complete organization invitation', function () {
    $user = invitationTestUser(
        active: false
    );

    $token = Password::broker(
        'organization_invitations'
    )->createToken($user);

    $this
        ->post(
            route(
                'organization-invitations.complete',
                ['token' => $token]
            ),
            [
                'email' => $user->email,
                'password' => 'NewInvitePass1!',
                'password_confirmation' =>
                    'NewInvitePass1!',
            ]
        )
        ->assertSessionHasErrors('email');

    expect(
        $user->fresh()->hasVerifiedEmail()
    )->toBeFalse();
});

test('invitation email contains account details but never exposes password value', function () {
    $user = invitationTestUser();

    $plainPasswordThatMustNeverAppear =
        'NeverEmailThisPassword9!';

    $user->forceFill([
        'name' =>
            'Invited Consent Manager',
        'email' =>
            'invited-content@example.com',
        'password' =>
            $plainPasswordThatMustNeverAppear,
    ])->save();

    $notification =
        new OrganizationUserInvitation(
            token:
                'mail-content-token',
            organizationName:
                'Invitation Test Organization',
            roleName:
                'Consent Manager'
        );

    $mail = $notification->toMail($user);

    $content = implode(
        "\n",
        array_filter(
            [
                $mail->subject,
                $mail->greeting,
                ...$mail->introLines,
                $mail->actionText,
                $mail->actionUrl,
                ...$mail->outroLines,
            ],
            static fn (mixed $value): bool =>
                is_string($value)
        )
    );

    expect($content)
        ->toContain(
            'Invitation Test Organization'
        )
        ->toContain(
            'Invited Consent Manager'
        )
        ->toContain(
            'Consent Manager'
        )
        ->toContain(
            'invited-content@example.com'
        )
        ->toContain(
            'Complete account setup'
        )
        ->toContain(
            '/organization-invitations/mail-content-token'
        )
        ->not->toContain(
            $plainPasswordThatMustNeverAppear
        );
});

