<?php

use App\Models\User;
use App\Notifications\BrandedResetPassword;
use App\Notifications\BrandedVerifyEmail;
use App\Notifications\QuotaResetPassword;
use App\Notifications\QuotaVerifyEmail;

test('verification email uses branded secure content', function () {
    $user = User::factory()
        ->unverified()
        ->create([
            'name' => 'Verification Test User',
            'email' => 'verification-content@example.com',
        ]);

    $notification =
        new BrandedVerifyEmail();

    $mail =
        $notification->toMail($user);

    $content =
        $mail->subject
        ."\n"
        .view(
            $mail->view,
            $mail->viewData
        )->render();

    expect($content)
        ->toContain(
            'Verify your email address'
        )
        ->toContain(
            'Verification Test User'
        )
        ->toContain(
            'Verify email address'
        )
        ->toContain(
            '60 minutes'
        )
        ->toContain(
            '/verify-email/'
        )
        ->toContain(
            sha1($user->email)
        )
        ->not->toContain(
            $user->password
        );
});

test('password reset email uses branded secure content', function () {
    $user = User::factory()
        ->create([
            'name' => 'Reset Test User',
            'email' => 'reset-content@example.com',
        ]);

    $notification =
        new BrandedResetPassword(
            'safe-reset-token'
        );

    $mail =
        $notification->toMail($user);

    $content =
        $mail->subject
        ."\n"
        .view(
            $mail->view,
            $mail->viewData
        )->render();

    expect($content)
        ->toContain(
            'Reset your eConsent password'
        )
        ->toContain(
            'Reset Test User'
        )
        ->toContain(
            'Reset password'
        )
        ->toContain(
            '60 minutes'
        )
        ->toContain(
            '/reset-password/safe-reset-token'
        )
        ->toContain(
            'reset-content%40example.com'
        )
        ->not->toContain(
            $user->password
        );
});

test('quota auth notifications inherit branded presentation', function () {
    expect(
        new QuotaVerifyEmail()
    )->toBeInstanceOf(
        BrandedVerifyEmail::class
    );

    expect(
        new QuotaResetPassword('token')
    )->toBeInstanceOf(
        BrandedResetPassword::class
    );
});
