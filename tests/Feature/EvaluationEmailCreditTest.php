<?php

use App\Jobs\SendConsentNotificationJob;
use App\Mail\ConsentSigningRequestMail;
use App\Models\ConsentNotification;
use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\EvaluationEmailCreditUsage;
use App\Models\Organization;
use App\Models\User;
use App\Services\ConsentNotificationService;
use App\Services\EvaluationEmailCreditService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);


function evaluationEmailTestContext(): array
{
    test()->seed(
        SubscriptionPlanSeeder::class
    );

    test()->post('/register', [
        'organization_name' =>
            'Evaluation Email Clinic',

        'name' =>
            'Evaluation Email Administrator',

        'email' =>
            'evaluation-email-admin@example.com',

        'password' =>
            'StrongPass1!',

        'password_confirmation' =>
            'StrongPass1!',
    ]);

    $organization =
        Organization::query()
            ->where(
                'name',
                'Evaluation Email Clinic'
            )
            ->firstOrFail();

    $administrator =
        User::query()
            ->where(
                'email',
                'evaluation-email-admin@example.com'
            )
            ->firstOrFail();

    $template =
        ConsentTemplate::query()->create([
            'organization_id' =>
                $organization->id,

            'title' =>
                'Evaluation Email Consent',

            'description' =>
                'Evaluation email credit test.',

            'category' =>
                'Testing',

            'usage_type' =>
                ConsentTemplate::USAGE_INDIVIDUAL,

            'template_schema' => [
                'sections' => [],
            ],

            'active_version_id' => null,

            'has_unpublished_changes' =>
                false,

            'status' => 'draft',
        ]);

    $version =
        $template
            ->versions()
            ->create([
                'version_number' => 1,

                'title' =>
                    $template->title,

                'description' =>
                    $template->description,

                'template_schema' =>
                    $template->template_schema,

                'published_at' => now(),

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

    return [
        $organization,
        $administrator,
        $template->refresh(),
    ];
}


function evaluationEmailSession(
    Organization $organization,
    User $administrator,
    ConsentTemplate $template,
    string $email
): ConsentSession {
    return ConsentSession::query()
        ->create([
            'organization_id' =>
                $organization->id,

            'consent_template_id' =>
                $template->id,

            'consent_template_version_id' =>
                $template->active_version_id,

            'signing_station_id' =>
                null,

            'consent_campaign_id' =>
                null,

            'created_by' =>
                $administrator->id,

            'signer_name' =>
                'Evaluation Signer',

            'signer_email' =>
                $email,

            'signer_reference' =>
                null,

            'access_token' =>
                (string) Str::uuid(),

            'status' =>
                ConsentSession::STATUS_PENDING,

            'responses' =>
                [],

            'expires_at' =>
                now()->addWeek(),

            'expired_at' =>
                null,
        ]);
}


test('evaluation receives five lifetime signing emails and sixth is blocked', function () {
    config([
        'email-quota.enabled' => false,
    ]);

    Mail::fake();

    [
        $organization,
        $administrator,
        $template,
    ] = evaluationEmailTestContext();

    $notificationService =
        app(
            ConsentNotificationService::class
        );

    for ($i = 1; $i <= 5; $i++) {
        $session = evaluationEmailSession(
            $organization,
            $administrator,
            $template,
            "evaluation{$i}@example.com"
        );

        $notification =
            $notificationService
                ->sendInitial(
                    consentSession:
                        $session,

                    actorUserId:
                        $administrator->id,

                    trigger:
                        ConsentNotification::
                            TRIGGER_AUTOMATIC_CREATION
                );

        expect($notification->isSent())
            ->toBeTrue();
    }

    $capacity =
        app(
            EvaluationEmailCreditService::class
        )->capacity(
            $organization->id
        );

    expect($capacity['is_evaluation'])
        ->toBeTrue();

    expect($capacity['limit'])
        ->toBe(5);

    expect($capacity['used'])
        ->toBe(5);

    expect($capacity['remaining'])
        ->toBe(0);

    expect($capacity['reached'])
        ->toBeTrue();

    expect(
        EvaluationEmailCreditUsage::query()
            ->where(
                'status',
                EvaluationEmailCreditUsage::
                    STATUS_CONSUMED
            )
            ->count()
    )->toBe(5);

    $sixthSession =
        evaluationEmailSession(
            $organization,
            $administrator,
            $template,
            'evaluation6@example.com'
        );

    try {
        $notificationService
            ->sendInitial(
                consentSession:
                    $sixthSession,

                actorUserId:
                    $administrator->id,

                trigger:
                    ConsentNotification::
                        TRIGGER_AUTOMATIC_CREATION
            );

        $this->fail(
            'The sixth Evaluation signing email was not blocked.'
        );
    } catch (
        ValidationException $exception
    ) {
        expect($exception->errors())
            ->toHaveKey('email');
    }

    expect(
        ConsentNotification::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->count()
    )->toBe(5);

    Mail::assertSent(
        ConsentSigningRequestMail::class,
        5
    );
});


test('resends and manual reminders consume evaluation credits', function () {
    config([
        'email-quota.enabled' => false,
    ]);

    Mail::fake();

    [
        $organization,
        $administrator,
        $template,
    ] = evaluationEmailTestContext();

    $session =
        evaluationEmailSession(
            $organization,
            $administrator,
            $template,
            'reminders@example.com'
        );

    $service =
        app(
            ConsentNotificationService::class
        );

    $initial =
        $service->sendInitial(
            consentSession: $session,
            actorUserId:
                $administrator->id,
            trigger:
                ConsentNotification::
                    TRIGGER_AUTOMATIC_CREATION
        );

    $resend =
        $service->sendInitial(
            consentSession: $session,
            actorUserId:
                $administrator->id,
            trigger:
                ConsentNotification::
                    TRIGGER_MANUAL
        );

    $reminder =
        $service->sendManualReminder(
            consentSession: $session,
            actorUserId:
                $administrator->id
        );

    expect($initial->type)
        ->toBe(
            ConsentNotification::TYPE_INITIAL
        );

    expect($resend->type)
        ->toBe(
            ConsentNotification::TYPE_RESEND
        );

    expect($reminder->type)
        ->toBe(
            ConsentNotification::
                TYPE_MANUAL_REMINDER
        );

    $capacity =
        app(
            EvaluationEmailCreditService::class
        )->capacity(
            $organization->id
        );

    expect($capacity['used'])->toBe(3);
    expect($capacity['remaining'])->toBe(2);

    Mail::assertSent(
        ConsentSigningRequestMail::class,
        3
    );
});


test('automatic consent reminders are disabled during evaluation', function () {
    config([
        'email-quota.enabled' => false,
    ]);

    Mail::fake();

    [
        $organization,
        $administrator,
        $template,
    ] = evaluationEmailTestContext();

    $session =
        evaluationEmailSession(
            $organization,
            $administrator,
            $template,
            'automatic@example.com'
        );

    $notification =
        app(
            ConsentNotificationService::class
        )->sendAutomaticReminder(
            $session,
            3
        );

    expect($notification)->toBeNull();

    expect(
        ConsentNotification::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->count()
    )->toBe(0);

    $capacity =
        app(
            EvaluationEmailCreditService::class
        )->capacity(
            $organization->id
        );

    expect($capacity['used'])->toBe(0);
    expect($capacity['remaining'])->toBe(5);

    Mail::assertNothingSent();
});


test('synchronous delivery failure releases evaluation credit', function () {
    config([
        'email-quota.enabled' => false,
    ]);

    [
        $organization,
        $administrator,
        $template,
    ] = evaluationEmailTestContext();

    $session =
        evaluationEmailSession(
            $organization,
            $administrator,
            $template,
            'failure@example.com'
        );

    Mail::shouldReceive('to')
        ->once()
        ->andThrow(
            new RuntimeException(
                'Simulated provider failure.'
            )
        );

    $notification =
        app(
            ConsentNotificationService::class
        )->sendInitial(
            consentSession:
                $session,

            actorUserId:
                $administrator->id,

            trigger:
                ConsentNotification::
                    TRIGGER_AUTOMATIC_CREATION
        );

    expect($notification->isFailed())
        ->toBeTrue();

    $usage =
        EvaluationEmailCreditUsage::query()
            ->sole();

    expect($usage->status)
        ->toBe(
            EvaluationEmailCreditUsage::
                STATUS_RELEASED
        );

    expect($usage->release_reason)
        ->toBe(
            'synchronous_delivery_failed'
        );

    $capacity =
        app(
            EvaluationEmailCreditService::class
        )->capacity(
            $organization->id
        );

    expect($capacity['used'])->toBe(0);
    expect($capacity['remaining'])->toBe(5);
});


test('terminal queue failure releases reserved evaluation credit', function () {
    [
        $organization,
        $administrator,
        $template,
    ] = evaluationEmailTestContext();

    $session =
        evaluationEmailSession(
            $organization,
            $administrator,
            $template,
            'queued-failure@example.com'
        );

    $creditService =
        app(
            EvaluationEmailCreditService::class
        );

    [
        $usage,
        $notification,
    ] = DB::transaction(
        function () use (
            $organization,
            $administrator,
            $session,
            $creditService
        ): array {
            $usage =
                $creditService
                    ->reserveLocked(
                        organizationId:
                            $organization->id,

                        consentSessionId:
                            $session->id,

                        notificationType:
                            ConsentNotification::
                                TYPE_INITIAL
                    );

            $notification =
                ConsentNotification::query()
                    ->create([
                        'organization_id' =>
                            $organization->id,

                        'consent_session_id' =>
                            $session->id,

                        'actor_user_id' =>
                            $administrator->id,

                        'type' =>
                            ConsentNotification::
                                TYPE_INITIAL,

                        'trigger' =>
                            ConsentNotification::
                                TRIGGER_AUTOMATIC_CREATION,

                        'status' =>
                            ConsentNotification::
                                STATUS_QUEUED,

                        'recipient_email' =>
                            $session->signer_email,

                        'subject' =>
                            'Consent request',

                        'message' =>
                            'Please review the consent.',

                        'days_before_deadline' =>
                            null,

                        'scheduled_for' =>
                            null,

                        'metadata' =>
                            [],
                    ]);

            $creditService
                ->attachNotificationLocked(
                    usageId:
                        $usage->id,

                    notificationId:
                        $notification->id,

                    notificationType:
                        ConsentNotification::
                            TYPE_INITIAL
                );

            return [
                $usage->refresh(),
                $notification->refresh(),
            ];
        }
    );

    expect($usage->status)
        ->toBe(
            EvaluationEmailCreditUsage::
                STATUS_RESERVED
        );

    expect(
        $creditService
            ->capacity(
                $organization->id
            )['used']
    )->toBe(1);

    $job =
        new SendConsentNotificationJob(
            $notification->id
        );

    $job->failed(
        new RuntimeException(
            'Queue retries exhausted.'
        )
    );

    $usage = $usage->refresh();

    expect($usage->status)
        ->toBe(
            EvaluationEmailCreditUsage::
                STATUS_RELEASED
        );

    expect($usage->release_reason)
        ->toBe(
            'queue_terminal_failure'
        );

    expect(
        $creditService
            ->capacity(
                $organization->id
            )['used']
    )->toBe(0);
});


test('paid organizations are not limited by evaluation email credits', function () {
    config([
        'email-quota.enabled' => false,
    ]);

    Mail::fake();

    [
        $organization,
        $administrator,
        $template,
    ] = evaluationEmailTestContext();

    $this->enablePaidOrganizationAccess(
        $organization,
        $administrator
    );

    $service =
        app(
            ConsentNotificationService::class
        );

    for ($i = 1; $i <= 6; $i++) {
        $session =
            evaluationEmailSession(
                $organization,
                $administrator,
                $template,
                "paid{$i}@example.com"
            );

        $notification =
            $service->sendInitial(
                consentSession:
                    $session,

                actorUserId:
                    $administrator->id,

                trigger:
                    ConsentNotification::
                        TRIGGER_AUTOMATIC_CREATION
            );

        expect($notification->isSent())
            ->toBeTrue();
    }

    expect(
        EvaluationEmailCreditUsage::query()
            ->count()
    )->toBe(0);

    $capacity =
        app(
            EvaluationEmailCreditService::class
        )->capacity(
            $organization->id
        );

    expect($capacity['is_evaluation'])
        ->toBeFalse();

    expect($capacity['limit'])->toBeNull();
    expect($capacity['remaining'])->toBeNull();

    Mail::assertSent(
        ConsentSigningRequestMail::class,
        6
    );
});
