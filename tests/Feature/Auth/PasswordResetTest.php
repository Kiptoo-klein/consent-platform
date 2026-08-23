<?php

use App\Models\User;
use App\Notifications\QuotaResetPassword;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-reset-password',
            'password_confirmation' => 'new-reset-password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $user->refresh();

        expect(
            Hash::check(
                'new-reset-password',
                $user->password
            )
        )->toBeTrue();

        expect(
            Hash::check(
                'password',
                $user->password
            )
        )->toBeFalse();

        return true;
    });
});

test('password reset uses quota aware notification when resend quota is enabled', function () {
    Notification::fake();

    config()->set(
        'email-quota.enabled',
        true
    );

    config()->set(
        'email-quota.only_mailer',
        'resend'
    );

    config()->set(
        'mail.default',
        'resend'
    );

    $user = User::factory()->create();

    $response = $this->post(
        '/forgot-password',
        [
            'email' => $user->email,
        ]
    );

    $response->assertSessionHasNoErrors();

    Notification::assertSentTo(
        $user,
        QuotaResetPassword::class
    );
});
