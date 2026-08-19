<?php

namespace Tests\Feature;

use App\Mail\ConsentSigningRequestMail;
use App\Models\ConsentNotification;
use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\User;
use App\Services\EvaluationEmailCreditService;
use App\Services\EvaluationOnboardingService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EvaluationSelfTestConsentTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $administrator;

    private ConsentTemplate $starter;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'email-quota.enabled' =>
                false,
        ]);

        $this->seed(
            SubscriptionPlanSeeder::class
        );

        $this
            ->post('/register', [
                'organization_name' =>
                    'Evaluation Self Test Clinic',

                'name' =>
                    'Evaluation Test Administrator',

                'email' =>
                    'self-test@example.com',

                'password' =>
                    'StrongPass1!',

                'password_confirmation' =>
                    'StrongPass1!',
            ])
            ->assertRedirect(
                route('dashboard')
            );

        $this->organization =
            Organization::query()
                ->where(
                    'name',
                    'Evaluation Self Test Clinic'
                )
                ->firstOrFail();

        $this->administrator =
            User::query()
                ->where(
                    'email',
                    'self-test@example.com'
                )
                ->firstOrFail();

        $this->starter =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->whereNotNull(
                    'evaluation_starter_key'
                )
                ->whereNull(
                    'evaluation_retired_at'
                )
                ->orderBy('id')
                ->firstOrFail();

        $this
            ->actingAs(
                $this->administrator
            )
            ->post(
                route(
                    'consent-templates.publish',
                    $this->starter
                )
            )
            ->assertRedirect(
                route(
                    'consent-templates.published',
                    $this->starter
                )
            );

        $this->starter->refresh();
    }

    public function test_evaluation_dashboard_links_to_self_test_template_selector(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route('dashboard')
            )
            ->assertOk()
            ->assertSeeText(
                'Send test to myself'
            )
            ->assertSee(
                route(
                    'consent-sessions.select-template',
                    [
                        'self_test' => 1,
                    ]
                ),
                false
            );
    }

    public function test_self_test_selector_and_form_prefill_authenticated_user(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route(
                    'consent-sessions.select-template',
                    [
                        'self_test' => 1,
                    ]
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Send Test Consent to Myself'
            )
            ->assertSeeText(
                'Use This Template'
            )
            ->assertDontSeeText(
                'Up to 20 People'
            );

        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route(
                    'consent-sessions.create',
                    [
                        'consentTemplate' =>
                            $this->starter,

                        'self_test' =>
                            1,
                    ]
                )
            )
            ->assertOk()
            ->assertSee(
                'data-evaluation-self-test',
                false
            )
            ->assertSee(
                'name="self_test"',
                false
            )
            ->assertSee(
                'value="1"',
                false
            )
            ->assertSee(
                'value="'.$this->administrator->name.'"',
                false
            )
            ->assertSee(
                'value="'.$this->administrator->email.'"',
                false
            )
            ->assertSeeText(
                '5 invitation emails remaining.'
            );
    }

    public function test_self_test_creates_real_record_sends_once_and_consumes_one_credit(): void
    {
        Mail::fake();

        $response =
            $this
                ->actingAs(
                    $this->administrator
                )
                ->post(
                    route(
                        'consent-sessions.store',
                        $this->starter
                    ),
                    [
                        'self_test' =>
                            1,

                        /*
                         * Deliberately tampered values prove that
                         * the server uses the logged-in user.
                         */
                        'signer_name' =>
                            'Someone Else',

                        'signer_email' =>
                            'someone-else@example.com',

                        'signer_reference' =>
                            null,

                        'expires_date' =>
                            null,

                        'expires_time' =>
                            null,
                    ]
                );

        $session =
            ConsentSession::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->sole();

        $response
            ->assertRedirect(
                route(
                    'consent-sessions.show',
                    $session
                )
            )
            ->assertSessionHas(
                'email_success'
            );

        $this->assertSame(
            $this->administrator->name,
            $session->signer_name
        );

        $this->assertSame(
            $this->administrator->email,
            $session->signer_email
        );

        $this->assertSame(
            $this->starter->id,
            $session->consent_template_id
        );

        $this->assertSame(
            $this->starter->active_version_id,
            $session->consent_template_version_id
        );

        $this->assertSame(
            ConsentSession::STATUS_PENDING,
            $session->status
        );

        $this->assertDatabaseHas(
            'consent_audit_events',
            [
                'consent_session_id' =>
                    $session->id,

                'event_type' =>
                    'consent.created',
            ]
        );

        $this->assertSame(
            1,
            ConsentNotification::query()
                ->where(
                    'consent_session_id',
                    $session->id
                )
                ->count()
        );

        $notification =
            ConsentNotification::query()
                ->where(
                    'consent_session_id',
                    $session->id
                )
                ->sole();

        $this->assertSame(
            ConsentNotification::TYPE_INITIAL,
            $notification->type
        );

        $this->assertSame(
            $this->administrator->email,
            $notification->recipient_email
        );

        $capacity =
            app(
                EvaluationEmailCreditService::class
            )->capacity(
                $this->organization->id
            );

        $this->assertSame(
            1,
            $capacity['used']
        );

        $this->assertSame(
            4,
            $capacity['remaining']
        );

        $onboarding =
            app(
                EvaluationOnboardingService::class
            )->dashboardState(
                $this->organization->id
            );

        $this->assertNotNull(
            $onboarding
        );

        $this->assertTrue(
            $onboarding[
                'checklist'
            ][
                'first_consent'
            ]
        );

        Mail::assertSent(
            ConsentSigningRequestMail::class,
            1
        );
    }

    public function test_legacy_signing_station_template_can_be_used_for_one_person(): void
    {
        $this->starter->update([
            'usage_type' =>
                'signing_station',
        ]);

        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route(
                    'consent-sessions.create',
                    [
                        'consentTemplate' =>
                            $this->starter,

                        'self_test' =>
                            1,
                    ]
                )
            )
            ->assertOk()
            ->assertSee(
                'data-evaluation-self-test',
                false
            );
    }

    public function test_sixth_self_test_is_blocked_before_record_creation(): void
    {
        Mail::fake();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this
                ->actingAs(
                    $this->administrator
                )
                ->post(
                    route(
                        'consent-sessions.store',
                        $this->starter
                    ),
                    [
                        'self_test' =>
                            1,

                        'signer_name' =>
                            'Tampered Name',

                        'signer_email' =>
                            "tampered{$attempt}@example.com",

                        'signer_reference' =>
                            null,

                        'expires_date' =>
                            null,

                        'expires_time' =>
                            null,
                    ]
                )
                ->assertRedirect();
        }

        $this->assertSame(
            5,
            ConsentSession::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->count()
        );

        $capacity =
            app(
                EvaluationEmailCreditService::class
            )->capacity(
                $this->organization->id
            );

        $this->assertSame(
            5,
            $capacity['used']
        );

        $this->assertSame(
            0,
            $capacity['remaining']
        );

        $this->assertTrue(
            $capacity['reached']
        );

        $this
            ->actingAs(
                $this->administrator
            )
            ->from(
                route('dashboard')
            )
            ->post(
                route(
                    'consent-sessions.store',
                    $this->starter
                ),
                [
                    'self_test' =>
                        1,

                    'signer_name' =>
                        'Sixth Attempt',

                    'signer_email' =>
                        'sixth@example.com',

                    'signer_reference' =>
                        null,

                    'expires_date' =>
                        null,

                    'expires_time' =>
                        null,
                ]
            )
            ->assertRedirect(
                route('dashboard')
            )
            ->assertSessionHasErrors(
                'email'
            );

        /*
         * The blocked sixth attempt must not leave behind an
         * unsent consent record.
         */
        $this->assertSame(
            5,
            ConsentSession::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->count()
        );

        $this->assertSame(
            5,
            ConsentNotification::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->count()
        );

        $capacity =
            app(
                EvaluationEmailCreditService::class
            )->capacity(
                $this->organization->id
            );

        $this->assertSame(
            5,
            $capacity['used']
        );

        $this->assertSame(
            0,
            $capacity['remaining']
        );

        Mail::assertSent(
            ConsentSigningRequestMail::class,
            5
        );
    }


    public function test_normal_individual_consent_form_remains_unchanged(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route(
                    'consent-sessions.create',
                    $this->starter
                )
            )
            ->assertOk()
            ->assertDontSee(
                'data-evaluation-self-test',
                false
            )
            ->assertDontSee(
                'name="self_test"',
                false
            )
            ->assertSeeText(
                'Create Individual Consent'
            );
    }
}
