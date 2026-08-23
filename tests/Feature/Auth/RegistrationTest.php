<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new organizations register into the free evaluation', function () {
    \Illuminate\Support\Facades\Notification::fake();

    $this->seed(
        \Database\Seeders\SubscriptionPlanSeeder::class
    );

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

    expect($user->organization_id)
        ->toBe($organization->id);

    expect($organization->email)
        ->toBe('test@example.com');

    \Illuminate\Support\Facades\Notification::assertSentTo(
        $user,
        \App\Notifications\BrandedVerifyEmail::class
    );

    \Illuminate\Support\Facades\Notification::assertNotSentTo(
        $user,
        \App\Notifications\OrganizationWelcome::class
    );

    app(
        \Spatie\Permission\PermissionRegistrar::class
    )->setPermissionsTeamId($organization->id);

    expect($user->fresh()->hasRole('Organization Admin'))
        ->toBeTrue();

    $subscription = $organization
        ->subscription()
        ->with('plan')
        ->firstOrFail();

    /*
     * Basic remains the relational plan placeholder while
     * the organization uses its free evaluation workspace.
     */
    expect($subscription->plan->slug)
        ->toBe('basic');

    expect($subscription->requires_plan_selection)
        ->toBeTrue();

    expect($subscription->requiresPlanSelection())
        ->toBeTrue();

    expect($subscription->plan_selected_at)
        ->toBeNull();

    expect($subscription->status)->toBe(
        \App\Enums\OrganizationSubscriptionStatus::EVALUATION
    );

    expect($subscription->payment_status)->toBe(
        \App\Enums\SubscriptionPaymentStatus::UNPAID
    );

    expect($subscription->billing_owner_user_id)
        ->toBe($user->id);

    expect($subscription->bypass_approved_at)
        ->toBeNull();

    expect($subscription->bypass_approved_by_user_id)
        ->toBeNull();

    expect($subscription->isEvaluation())
        ->toBeTrue();

    expect($subscription->allowsOrganizationAccess())
        ->toBeTrue();

    $response->assertRedirect(
        route(
            'verification.notice',
            absolute: false
        )
    );
});

test('verification notification failure does not undo registration', function () {
    $this->seed(
        \Database\Seeders\SubscriptionPlanSeeder::class
    );

    $dispatcher = \Mockery::mock(
        \Illuminate\Contracts\Notifications\Dispatcher::class
    );

    $dispatcher
        ->shouldReceive('send')
        ->once()
        ->andThrow(
            new \RuntimeException(
                'Notification dispatch unavailable.'
            )
        );

    $this->app->instance(
        \Illuminate\Contracts\Notifications\Dispatcher::class,
        $dispatcher
    );

    $response = $this->post('/register', [
        'organization_name' =>
            'Delivery Failure Health Centre',
        'name' =>
            'Delivery Failure Administrator',
        'email' =>
            'delivery-failure@example.com',
        'password' =>
            'password',
        'password_confirmation' =>
            'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(
            route(
                'verification.notice',
                absolute: false
            )
        );

    $this->assertAuthenticated();

    $organization = \App\Models\Organization::query()
        ->where(
            'name',
            'Delivery Failure Health Centre'
        )
        ->firstOrFail();

    $user = \App\Models\User::query()
        ->where(
            'email',
            'delivery-failure@example.com'
        )
        ->firstOrFail();

    expect($user->organization_id)
        ->toBe($organization->id);

    expect($organization->email)
        ->toBe('delivery-failure@example.com');

    expect(
        $organization
            ->subscription()
            ->firstOrFail()
            ->isEvaluation()
    )->toBeTrue();
});

test('founder receives welcome exactly once after verifying email', function () {
    \Illuminate\Support\Facades\Notification::fake();

    $this->seed(
        \Database\Seeders\SubscriptionPlanSeeder::class
    );

    $this
        ->post('/register', [
            'organization_name' =>
                'Verified Welcome Clinic',
            'name' =>
                'Verified Welcome Administrator',
            'email' =>
                'verified-welcome@example.com',
            'password' =>
                'StrongPass1!',
            'password_confirmation' =>
                'StrongPass1!',
        ])
        ->assertRedirect(
            route(
                'verification.notice',
                absolute: false
            )
        );

    $user = \App\Models\User::query()
        ->where(
            'email',
            'verified-welcome@example.com'
        )
        ->firstOrFail();

    expect(
        $user->hasVerifiedEmail()
    )->toBeFalse();

    \Illuminate\Support\Facades\Notification::assertNotSentTo(
        $user,
        \App\Notifications\OrganizationWelcome::class
    );

    $verificationUrl =
        \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

    $this
        ->get($verificationUrl)
        ->assertRedirect(
            route(
                'dashboard',
                absolute: false
            ).'?verified=1'
        );

    expect(
        $user->fresh()->hasVerifiedEmail()
    )->toBeTrue();

    $welcomeNotifications =
        \Illuminate\Support\Facades\Notification::sent(
            $user,
            \App\Notifications\OrganizationWelcome::class
        );

    expect(
        $welcomeNotifications
    )->toHaveCount(1);

    $welcomeMail =
        $welcomeNotifications
            ->first()
            ->toMail($user);

    $welcomeContent =
        $welcomeMail->subject
        ."\n"
        .view(
            $welcomeMail->view,
            $welcomeMail->viewData
        )->render();

    expect($welcomeContent)
        ->toContain(
            'Welcome to eConsent'
        )
        ->toContain(
            'Verified Welcome Administrator'
        )
        ->toContain(
            'Verified Welcome Clinic'
        )
        ->toContain(
            'Free Evaluation'
        )
        ->toContain(
            'Open your eConsent dashboard'
        )
        ->toContain(
            'No time limit'
        )
        ->toContain(
            route('dashboard')
        )
        ->not->toContain(
            'StrongPass1!'
        );

    /*
     * A repeated click on the same still-valid signed URL must not
     * produce another welcome message.
     */
    $this
        ->get($verificationUrl)
        ->assertRedirect(
            route(
                'dashboard',
                absolute: false
            ).'?verified=1'
        );

    expect(
        \Illuminate\Support\Facades\Notification::sent(
            $user,
            \App\Notifications\OrganizationWelcome::class
        )
    )->toHaveCount(1);
});
