<?php

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\User;
use App\Notifications\SupportRequestSubmitted;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(
        PlatformRoleSeeder::class
    );
});

function supportRequestUser(): array
{
    $organization =
        Organization::query()->create([
            'name' =>
                'Support Request Organization',
            'slug' =>
                'support-request-organization',
        ]);

    $user =
        User::factory()->create([
            'organization_id' =>
                $organization->id,
            'platform_role_id' =>
                null,
            'is_active' =>
                true,
            'email_verified_at' =>
                now(),
        ]);

    RateLimiter::clear(
        'support-request:user:'
        .$user->id
    );

    return [
        $organization,
        $user,
    ];
}

function supportRequestSuperAdmin(
    array $overrides = []
): User {
    $role =
        PlatformRole::query()
            ->where(
                'slug',
                'super-admin'
            )
            ->firstOrFail();

    return User::factory()->create(
        array_merge(
            [
                'organization_id' =>
                    null,
                'platform_role_id' =>
                    $role->id,
                'is_active' =>
                    true,
                'email_verified_at' =>
                    now(),
            ],
            $overrides
        )
    );
}

test(
    'contextual help offers question and problem support',
    function () {
        [
            $organization,
            $user,
        ] = supportRequestUser();

        supportRequestSuperAdmin();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'organization-settings.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-support-request',
                false
            )
            ->assertSeeText(
                'Still need help?'
            )
            ->assertSeeText(
                'Ask a question'
            )
            ->assertSeeText(
                'Report a problem'
            )
            ->assertSeeText(
                'Send to eConsent Support'
            );
    }
);

test(
    'organization user support request receives reference and is safely audited',
    function () {
        Notification::fake();

        [
            $organization,
            $user,
        ] = supportRequestUser();

        $superAdmin =
            supportRequestSuperAdmin([
                'email' =>
                    'super-admin@example.com',
            ]);

        $message =
            'How do I change the consent workflow '
            .'for this page?';

        $response =
            $this
                ->actingAs($user)
                ->from('/settings')
                ->post(
                    route(
                        'support-request.store'
                    ),
                    [
                        'support_type' =>
                            'question',
                        'support_message' =>
                            $message,
                        'support_context_route' =>
                            'organization-settings.index',
                        'support_context_path' =>
                            '/settings',
                        'support_help_context' =>
                            'settings',
                    ]
                );

        $response
            ->assertRedirect('/settings')
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'support_request_status'
            );

        expect(
            session(
                'support_request_status'
            )
        )->toMatch(
            '/Reference: SUP-\d{8}-[A-Z0-9]{8}\./'
        );

        Notification::assertSentTo(
            $superAdmin,
            SupportRequestSubmitted::class,
            function (
                SupportRequestSubmitted $notification
            ) use (
                $organization,
                $user
            ): bool {
                expect(
                    $notification
                        ->supportReference
                )->toMatch(
                    '/^SUP-\d{8}-[A-Z0-9]{8}$/'
                );

                return
                    $notification
                        ->requestType
                        === 'question'
                    && $notification
                        ->requesterName
                        === $user->name
                    && $notification
                        ->requesterEmail
                        === $user->email
                    && $notification
                        ->organizationName
                        === $organization->name
                    && $notification
                        ->pageRoute
                        ===
                        'organization-settings.index'
                    && $notification
                        ->pagePath
                        === '/settings'
                    && $notification
                        ->helpContext
                        === 'settings';
            }
        );

        $activity =
            ActivityLog::query()
                ->where(
                    'action',
                    'support.requested'
                )
                ->firstOrFail();

        $rawProperties =
            (string) $activity
                ->getRawOriginal(
                    'properties'
                );

        $properties =
            json_decode(
                $rawProperties,
                true
            );

        expect(
            $properties['reference'] ?? null
        )->toMatch(
            '/^SUP-\d{8}-[A-Z0-9]{8}$/'
        );

        expect(
            $properties[
                'request_type'
            ] ?? null
        )->toBe('question');

        expect(
            (int) (
                $properties[
                    'requester_user_id'
                ] ?? 0
            )
        )->toBe(
            (int) $user->id
        );

        expect(
            array_key_exists(
                'support_message',
                $properties
            )
        )->toBeFalse();

        expect(
            array_key_exists(
                'message_body',
                $properties
            )
        )->toBeFalse();

        expect(
            $rawProperties
        )->not->toContain(
            $message
        );
    }
);

test(
    'support request is sent to every active verified super admin',
    function () {
        Notification::fake();

        [
            $organization,
            $user,
        ] = supportRequestUser();

        $first =
            supportRequestSuperAdmin([
                'email' =>
                    'first-super-admin@example.com',
            ]);

        $second =
            supportRequestSuperAdmin([
                'email' =>
                    'second-super-admin@example.com',
            ]);

        $inactive =
            supportRequestSuperAdmin([
                'email' =>
                    'inactive-super-admin@example.com',
                'is_active' =>
                    false,
            ]);

        $unverified =
            supportRequestSuperAdmin([
                'email' =>
                    'unverified-super-admin@example.com',
                'email_verified_at' =>
                    null,
            ]);

        $this
            ->actingAs($user)
            ->post(
                route(
                    'support-request.store'
                ),
                [
                    'support_type' =>
                        'problem',
                    'support_message' =>
                        'Something is not working correctly '
                        .'on this page.',
                ]
            )
            ->assertSessionHasNoErrors();

        Notification::assertSentTo(
            $first,
            SupportRequestSubmitted::class
        );

        Notification::assertSentTo(
            $second,
            SupportRequestSubmitted::class
        );

        Notification::assertNotSentTo(
            $inactive,
            SupportRequestSubmitted::class
        );

        Notification::assertNotSentTo(
            $unverified,
            SupportRequestSubmitted::class
        );
    }
);

test(
    'support request validates type and message',
    function () {
        Notification::fake();

        [
            $organization,
            $user,
        ] = supportRequestUser();

        supportRequestSuperAdmin();

        $this
            ->actingAs($user)
            ->from('/settings')
            ->post(
                route(
                    'support-request.store'
                ),
                [
                    'support_type' =>
                        'other',
                    'support_message' =>
                        'short',
                ]
            )
            ->assertRedirect('/settings')
            ->assertSessionHasErrorsIn(
                'supportRequest',
                [
                    'support_type',
                    'support_message',
                ]
            );

        Notification::assertNothingSent();
    }
);

test(
    'support request requires an active verified super admin',
    function () {
        Notification::fake();

        [
            $organization,
            $user,
        ] = supportRequestUser();

        $this
            ->actingAs($user)
            ->from('/settings')
            ->post(
                route(
                    'support-request.store'
                ),
                [
                    'support_type' =>
                        'problem',
                    'support_message' =>
                        'The consent page is not loading '
                        .'as expected for our staff.',
                ]
            )
            ->assertRedirect('/settings')
            ->assertSessionHasErrorsIn(
                'supportRequest',
                'support'
            );

        Notification::assertNothingSent();
    }
);

test(
    'support requests are limited to five per hour per user',
    function () {
        Notification::fake();

        [
            $organization,
            $user,
        ] = supportRequestUser();

        supportRequestSuperAdmin();

        for (
            $attempt = 1;
            $attempt <= 5;
            $attempt++
        ) {
            $this
                ->actingAs($user)
                ->from('/settings')
                ->post(
                    route(
                        'support-request.store'
                    ),
                    [
                        'support_type' =>
                            'question',
                        'support_message' =>
                            'Support question number '
                            .$attempt
                            .' with enough detail.',
                    ]
                )
                ->assertSessionHasNoErrors();
        }

        $this
            ->actingAs($user)
            ->from('/settings')
            ->post(
                route(
                    'support-request.store'
                ),
                [
                    'support_type' =>
                        'question',
                    'support_message' =>
                        'This sixth support request '
                        .'should be rate limited.',
                ]
            )
            ->assertRedirect('/settings')
            ->assertSessionHasErrorsIn(
                'supportRequest',
                'support_message'
            );
    }
);

test(
    'support notification is queue and quota safe',
    function () {
        $notification =
            new SupportRequestSubmitted(
                supportReference:
                    'SUP-20260823-ABC12345',
                requestType:
                    'problem',
                requesterName:
                    'Support User',
                requesterEmail:
                    'support-user@example.com',
                organizationName:
                    'Support Organization',
                messageBody:
                    'A detailed problem report.',
                pageRoute:
                    'dashboard',
                pagePath:
                    '/dashboard',
                helpContext:
                    'dashboard',
                submittedAt:
                    'Aug 23, 2026 20:00 EAT'
            );

        expect(
            $notification->tries
        )->toBe(1000);

        expect(
            $notification->maxExceptions
        )->toBe(3);

        $middleware =
            $notification->middleware();

        expect($middleware)
            ->toHaveCount(1);

        expect($middleware[0])
            ->toBeInstanceOf(
                EnforceEmailQuota::class
            );
    }
);

test(
    'support email view renders reference request and page context',
    function () {
        $html =
            view(
                'emails.support-request-submitted',
                [
                    'supportReference' =>
                        'SUP-20260823-ABC12345',
                    'requestType' =>
                        'problem',
                    'requesterName' =>
                        'Support User',
                    'requesterEmail' =>
                        'support-user@example.com',
                    'organizationName' =>
                        'Support Organization',
                    'messageBody' =>
                        'The page returned an unexpected error.',
                    'pageRoute' =>
                        'dashboard',
                    'pagePath' =>
                        '/dashboard',
                    'helpContext' =>
                        'dashboard',
                    'submittedAt' =>
                        'Aug 23, 2026 20:00 EAT',
                ]
            )
            ->render();

        expect($html)
            ->toContain(
                'SUP-20260823-ABC12345'
            )
            ->toContain(
                'Problem reported by Support Organization'
            )
            ->toContain(
                'The page returned an unexpected error.'
            )
            ->toContain(
                'support-user@example.com'
            )
            ->toContain(
                '/dashboard'
            );
    }
);
