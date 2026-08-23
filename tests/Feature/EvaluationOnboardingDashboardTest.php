<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\ActivityLog;
use App\Models\ConsentNotification;
use App\Models\ConsentTemplate;
use App\Models\EvaluationEmailCreditUsage;
use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\User;
use App\Services\EvaluationOnboardingService;
use App\Services\EvaluationStarterTemplateService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvaluationOnboardingDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            SubscriptionPlanSeeder::class
        );

        $this
            ->post('/register', [
                'organization_name' =>
                    'Evaluation Onboarding Clinic',

                'name' =>
                    'Evaluation Onboarding Administrator',

                'email' =>
                    'evaluation-onboarding@example.com',

                'password' =>
                    'StrongPass1!',

                'password_confirmation' =>
                    'StrongPass1!',
            ])
            ->assertRedirect(
                route('verification.notice')
            );

        $this->verifyAuthenticatedUser();

        $this->organization =
            Organization::query()
                ->where(
                    'name',
                    'Evaluation Onboarding Clinic'
                )
                ->firstOrFail();

        $this->administrator =
            User::query()
                ->where(
                    'email',
                    'evaluation-onboarding@example.com'
                )
                ->firstOrFail();
    }

    public function test_new_evaluation_dashboard_shows_initial_usage_and_checklist(): void
    {
        $state = $this->state();

        $this->assertSame(
            0,
            $state['usage']['emails']['used']
        );

        $this->assertSame(
            5,
            $state['usage']['emails']['limit']
        );

        $this->assertSame(
            3,
            $state['usage']['templates']['used']
        );

        $this->assertSame(
            5,
            $state['usage']['templates']['limit']
        );

        $this->assertSame(
            0,
            $state['usage']['signing_stations']['used']
        );

        $this->assertSame(
            1,
            $state['usage']['signing_stations']['limit']
        );

        $this->assertSame(
            0,
            $state['usage']['completed_consents']['used']
        );

        $this->assertSame(
            5,
            $state['usage']['completed_consents']['limit']
        );

        $this->assertSame(
            [
                'profile' => false,
                'starter_reviewed' => false,
                'template_published' => false,
                'first_consent' => false,
                'signing_station' => false,
            ],
            $state['checklist']
        );

        $this->assertSame(
            0,
            $state['completed_steps']
        );

        $this->assertSame(
            5,
            $state['total_steps']
        );

        $this->assertFalse(
            $state['complete']
        );

        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route('dashboard')
            )
            ->assertOk()
            ->assertSee(
                'data-evaluation-dashboard',
                false
            )
            ->assertSee(
                'data-evaluation-email-usage="0/5"',
                false
            )
            ->assertSee(
                'data-evaluation-template-usage="3/5"',
                false
            )
            ->assertSee(
                'data-evaluation-signing-station-usage="0/1"',
                false
            )
            ->assertSee(
                'data-evaluation-consent-usage="0/5"',
                false
            )
            ->assertSee(
                'data-evaluation-checklist-progress="0/5"',
                false
            )
            ->assertSee(
                'data-evaluation-checklist-complete="0"',
                false
            )
            ->assertSeeText(
                'Free Evaluation'
            )
            ->assertSeeText(
                'No time limit'
            )
            ->assertSeeText(
                'Complete organization profile'
            )
            ->assertSeeText(
                'Review a starter template'
            )
            ->assertSeeText(
                'Publish your first template'
            )
            ->assertSeeText(
                'Send or complete your first consent'
            )
            ->assertSeeText(
                'Create your first signing station'
            );
    }

    public function test_checklist_progress_is_derived_from_real_evaluation_activity(): void
    {
        /*
         * Step 1: complete the practical organization profile.
         */
        $this->organization->update([
            'email' =>
                'clinic@example.com',

            'phone' =>
                '+254700000000',

            'address' =>
                'Nairobi, Kenya',
        ]);

        $state = $this->state();

        $this->assertTrue(
            $state['checklist']['profile']
        );

        $this->assertSame(
            1,
            $state['completed_steps']
        );

        /*
         * Step 2: genuinely save an Evaluation starter through
         * the production template-update route.
         */
        $starter = $this->starter();

        $this
            ->actingAs(
                $this->administrator
            )
            ->put(
                route(
                    'consent-templates.update',
                    $starter
                ),
                [
                    'title' =>
                        $starter->title,

                    'description' =>
                        'Reviewed during Evaluation onboarding.',

                    'usage_types' => [
                        ConsentTemplate::
                            USAGE_INDIVIDUAL,

                        ConsentTemplate::
                            USAGE_SIGNING_STATION,
                    ],

                    'content' =>
                        '<p>Reviewed starter consent content.</p>',

                    'additional_fields_json' =>
                        '[]',
                ]
            )
            ->assertRedirect(
                route(
                    'consent-templates.manage'
                )
            );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'organization_id' =>
                    $this->organization->id,

                'action' =>
                    EvaluationOnboardingService::
                        STARTER_REVIEWED_ACTION,

                'subject_type' =>
                    $starter->getMorphClass(),

                'subject_id' =>
                    $starter->id,
            ]
        );

        $state = $this->state();

        $this->assertTrue(
            $state['checklist']['starter_reviewed']
        );

        $this->assertSame(
            2,
            $state['completed_steps']
        );

        /*
         * Step 3: publish the starter through the production route.
         */
        $this
            ->actingAs(
                $this->administrator
            )
            ->post(
                route(
                    'consent-templates.publish',
                    $starter
                )
            )
            ->assertRedirect(
                route(
                    'consent-templates.published',
                    $starter
                )
            );

        $state = $this->state();

        $this->assertTrue(
            $state['checklist']['template_published']
        );

        $this->assertSame(
            3,
            $state['completed_steps']
        );

        /*
         * Step 4: a consumed Evaluation invitation credit proves
         * that the organization has sent a consent invitation.
         *
         * Consumed credits deliberately survive deletion of their
         * source notification/session, so no duplicate checklist
         * state is necessary.
         */
        EvaluationEmailCreditUsage::query()
            ->create([
                'organization_id' =>
                    $this->organization->id,

                'consent_session_id' =>
                    null,

                'consent_notification_id' =>
                    null,

                'reservation_key' =>
                    (string) Str::uuid(),

                'notification_type' =>
                    ConsentNotification::
                        TYPE_INITIAL,

                'status' =>
                    EvaluationEmailCreditUsage::
                        STATUS_CONSUMED,

                'reserved_at' =>
                    now()->subMinute(),

                'reservation_expires_at' =>
                    null,

                'consumed_at' =>
                    now(),

                'released_at' =>
                    null,

                'release_reason' =>
                    null,

                'metadata' => [
                    'source' =>
                        'onboarding-test',
                ],
            ]);

        $state = $this->state();

        $this->assertTrue(
            $state['checklist']['first_consent']
        );

        $this->assertSame(
            1,
            $state['usage']['emails']['used']
        );

        $this->assertSame(
            4,
            $state['usage']['emails']['remaining']
        );

        $this->assertSame(
            4,
            $state['completed_steps']
        );

        /*
         * Step 5: create the first signing station.
         *
         * The starter has already been published above, matching
         * the real onboarding order.
         */
        SigningStation::query()
            ->create([
                'organization_id' =>
                    $this->organization->id,

                'consent_template_id' =>
                    $starter->id,

                'created_by' =>
                    $this->administrator->id,

                'name' =>
                    'Evaluation Front Desk',

                'station_token' =>
                    Str::random(64),

                'qr_expires_at' =>
                    now()->addHours(
                        SigningStation::
                            QR_WINDOW_HOURS
                    ),

                'active' =>
                    true,

                'require_email' =>
                    false,

                'require_reference' =>
                    false,

                'auto_reset_seconds' =>
                    3,
            ]);

        $state = $this->state();

        $this->assertTrue(
            $state['checklist']['signing_station']
        );

        $this->assertSame(
            1,
            $state['usage']['signing_stations']['used']
        );

        $this->assertSame(
            5,
            $state['completed_steps']
        );

        $this->assertTrue(
            $state['complete']
        );

        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route('dashboard')
            )
            ->assertOk()
            ->assertSee(
                'data-evaluation-email-usage="1/5"',
                false
            )
            ->assertSee(
                'data-evaluation-signing-station-usage="1/1"',
                false
            )
            ->assertSee(
                'data-evaluation-checklist-progress="5/5"',
                false
            )
            ->assertSee(
                'data-evaluation-checklist-complete="1"',
                false
            )
            ->assertSeeText(
                'Complete'
            );
    }

    public function test_repeated_starter_saves_do_not_duplicate_review_activity(): void
    {
        $starter = $this->starter();

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this
                ->actingAs(
                    $this->administrator
                )
                ->put(
                    route(
                        'consent-templates.update',
                        $starter
                    ),
                    [
                        'title' =>
                            $starter->title,

                        'description' =>
                            "Review attempt {$attempt}.",

                        'usage_types' => [
                            ConsentTemplate::
                                USAGE_INDIVIDUAL,

                            ConsentTemplate::
                                USAGE_SIGNING_STATION,
                        ],

                        'content' =>
                            '<p>Reviewed starter content.</p>',

                        'additional_fields_json' =>
                            '[]',
                    ]
                )
                ->assertRedirect(
                    route(
                        'consent-templates.manage'
                    )
                );
        }

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->where(
                    'action',
                    EvaluationOnboardingService::
                        STARTER_REVIEWED_ACTION
                )
                ->where(
                    'subject_type',
                    $starter->getMorphClass()
                )
                ->where(
                    'subject_id',
                    $starter->id
                )
                ->count()
        );
    }

    public function test_paid_dashboard_hides_evaluation_panels_and_retired_starters(): void
    {
        $subscription =
            $this->organization
                ->subscription()
                ->firstOrFail();

        $subscription->update([
            'status' =>
                OrganizationSubscriptionStatus::
                    ACTIVE,

            'payment_status' =>
                SubscriptionPaymentStatus::
                    PAID,

            'requires_plan_selection' =>
                false,

            'plan_selected_at' =>
                now(),

            'current_period_starts_at' =>
                now(),

            'current_period_ends_at' =>
                now()->addMonth(),
        ]);

        app(
            EvaluationStarterTemplateService::class
        )->retireForOrganization(
            $this->organization->id
        );

        $this->assertNull(
            app(
                EvaluationOnboardingService::class
            )->dashboardState(
                $this->organization->id
            )
        );

        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route('dashboard')
            )
            ->assertOk()
            ->assertDontSee(
                'data-evaluation-dashboard',
                false
            )
            ->assertDontSeeText(
                'General Consent'
            )
            ->assertDontSeeText(
                'Photo & Media Consent'
            )
            ->assertDontSeeText(
                'Data / Information Consent'
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function state(): array
    {
        $state =
            app(
                EvaluationOnboardingService::class
            )->dashboardState(
                $this->organization->id
            );

        $this->assertNotNull(
            $state
        );

        return $state;
    }

    private function starter(): ConsentTemplate
    {
        return ConsentTemplate::query()
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
    }
}
