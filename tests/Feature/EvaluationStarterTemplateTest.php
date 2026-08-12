<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\EvaluationStarterTemplateService;
use App\Services\SubscriptionUsageLimitService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationStarterTemplateTest extends TestCase
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

        $this->post('/register', [
            'organization_name' =>
                'Starter Template Clinic',

            'name' =>
                'Starter Administrator',

            'email' =>
                'starter-admin@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ])->assertRedirect(
            route('dashboard')
        );

        $this->organization =
            Organization::query()
                ->where(
                    'name',
                    'Starter Template Clinic'
                )
                ->firstOrFail();

        $this->administrator =
            User::query()
                ->where(
                    'email',
                    'starter-admin@example.com'
                )
                ->firstOrFail();
    }

    public function test_registration_provisions_three_editable_evaluation_starter_templates(): void
    {
        $starters =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->whereNotNull(
                    'evaluation_starter_key'
                )
                ->orderBy(
                    'evaluation_starter_key'
                )
                ->get();

        $this->assertCount(
            3,
            $starters
        );

        $this->assertEqualsCanonicalizing(
            [
                EvaluationStarterTemplateService::
                    GENERAL_CONSENT,

                EvaluationStarterTemplateService::
                    PHOTO_MEDIA_CONSENT,

                EvaluationStarterTemplateService::
                    DATA_INFORMATION_CONSENT,
            ],
            $starters
                ->pluck(
                    'evaluation_starter_key'
                )
                ->all()
        );

        $this->assertEqualsCanonicalizing(
            [
                'General Consent',
                'Photo & Media Consent',
                'Data / Information Consent',
            ],
            $starters
                ->pluck('title')
                ->all()
        );

        foreach ($starters as $starter) {
            $this->assertSame(
                ConsentTemplate::USAGE_BOTH,
                $starter->usage_type
            );

            $this->assertSame(
                'draft',
                $starter->status
            );

            $this->assertNull(
                $starter->active_version_id
            );

            $this->assertTrue(
                $starter->has_unpublished_changes
            );

            $this->assertNull(
                $starter->evaluation_retired_at
            );

            $this->assertSame(
                2,
                $starter->template_schema[
                    'builder_version'
                ]
            );

            $this->assertNotEmpty(
                $starter->template_schema[
                    'consent_html'
                ]
            );

            $this->assertNotEmpty(
                $starter->template_schema[
                    'consent_text'
                ]
            );

            $this->assertSame(
                [],
                $starter->template_schema[
                    'additional_fields'
                ]
            );

            $this->assertTrue(
                $starter->isEvaluationStarter()
            );

            $this->assertFalse(
                $starter
                    ->isRetiredEvaluationStarter()
            );
        }

        $this->assertSame(
            3,
            app(
                SubscriptionUsageLimitService::class
            )->templateUsage(
                $this->organization->id
            )
        );
    }

    public function test_provisioning_is_idempotent_and_never_overwrites_user_edits(): void
    {
        $starter =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->where(
                    'evaluation_starter_key',
                    EvaluationStarterTemplateService::
                        GENERAL_CONSENT
                )
                ->firstOrFail();

        $starter->update([
            'title' =>
                'Our Edited General Consent',
        ]);

        app(
            EvaluationStarterTemplateService::class
        )->provision(
            $this->organization
        );

        $this->assertSame(
            3,
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->whereNotNull(
                    'evaluation_starter_key'
                )
                ->count()
        );

        $this->assertSame(
            'Our Edited General Consent',
            $starter->fresh()->title
        );
    }

    public function test_successful_paid_transition_permanently_retires_starters_but_preserves_custom_templates(): void
    {
        $starter =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->where(
                    'evaluation_starter_key',
                    EvaluationStarterTemplateService::
                        GENERAL_CONSENT
                )
                ->firstOrFail();

        /*
         * Simulate the organization having published and used a
         * starter before upgrading.
         */
        $version =
            $starter
                ->versions()
                ->create([
                    'version_number' =>
                        1,

                    'title' =>
                        $starter->title,

                    'description' =>
                        $starter->description,

                    'template_schema' =>
                        $starter->template_schema,

                    'published_at' =>
                        now(),

                    'published_by' =>
                        $this->administrator->id,
                ]);

        $starter->update([
            'active_version_id' =>
                $version->id,

            'has_unpublished_changes' =>
                false,

            'status' =>
                'published',
        ]);

        $custom =
            ConsentTemplate::query()
                ->create([
                    'organization_id' =>
                        $this->organization->id,

                    'title' =>
                        'Permanent Custom Template',

                    'description' =>
                        'Created by the organization.',

                    'category' =>
                        'Custom',

                    'usage_type' =>
                        ConsentTemplate::
                            USAGE_INDIVIDUAL,

                    'template_schema' => [
                        'builder_version' => 2,
                        'consent_html' =>
                            '<p>Custom consent.</p>',
                        'consent_text' =>
                            'Custom consent.',
                        'additional_fields' =>
                            [],
                    ],

                    'active_version_id' =>
                        null,

                    'has_unpublished_changes' =>
                        true,

                    'status' =>
                        'draft',
                ]);

        /*
         * This service is what the real invoice-payment transaction
         * calls immediately after activating the paid subscription.
         */
        app(
            EvaluationStarterTemplateService::class
        )->retireForOrganization(
            $this->organization->id
        );

        $starters =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->whereNotNull(
                    'evaluation_starter_key'
                )
                ->get();

        $this->assertCount(
            3,
            $starters
        );

        foreach ($starters as $retired) {
            $this->assertSame(
                'archived',
                $retired->status
            );

            $this->assertNull(
                $retired->active_version_id
            );

            $this->assertNotNull(
                $retired->evaluation_retired_at
            );

            $this->assertTrue(
                $retired
                    ->isRetiredEvaluationStarter()
            );
        }

        /*
         * Published history is preserved rather than deleted.
         */
        $this->assertDatabaseHas(
            'consent_template_versions',
            [
                'id' => $version->id,
                'consent_template_id' =>
                    $starter->id,
            ]
        );

        $this->assertDatabaseHas(
            'consent_templates',
            [
                'id' => $custom->id,
                'title' =>
                    'Permanent Custom Template',

                'evaluation_starter_key' =>
                    null,

                'evaluation_retired_at' =>
                    null,
            ]
        );
    }

    public function test_retirement_is_permanent_and_provisioning_does_not_restore_starters(): void
    {
        $service =
            app(
                EvaluationStarterTemplateService::class
            );

        $service->retireForOrganization(
            $this->organization->id
        );

        $retiredAt =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->whereNotNull(
                    'evaluation_starter_key'
                )
                ->pluck(
                    'evaluation_retired_at'
                );

        $this->assertCount(
            3,
            $retiredAt
        );

        $this->assertTrue(
            $retiredAt->every(
                fn ($date): bool =>
                    $date !== null
            )
        );

        /*
         * Provisioning can never resurrect the retired rows.
         */
        $service->provision(
            $this->organization
        );

        $this->assertSame(
            3,
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->whereNotNull(
                    'evaluation_starter_key'
                )
                ->count()
        );

        $this->assertSame(
            3,
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->whereNotNull(
                    'evaluation_retired_at'
                )
                ->count()
        );
    }

    public function test_retired_starters_are_hidden_from_active_and_archived_template_management(): void
    {
        $service =
            app(
                EvaluationStarterTemplateService::class
            );

        $service->retireForOrganization(
            $this->organization->id
        );

        $custom =
            ConsentTemplate::query()
                ->create([
                    'organization_id' =>
                        $this->organization->id,

                    'title' =>
                        'Visible Paid Template',

                    'description' =>
                        'Should remain visible.',

                    'category' =>
                        'Custom',

                    'usage_type' =>
                        ConsentTemplate::
                            USAGE_INDIVIDUAL,

                    'template_schema' => [
                        'builder_version' => 2,
                        'consent_html' =>
                            '<p>Visible.</p>',
                        'consent_text' =>
                            'Visible.',
                        'additional_fields' =>
                            [],
                    ],

                    'active_version_id' =>
                        null,

                    'has_unpublished_changes' =>
                        true,

                    'status' =>
                        'draft',
                ]);

        $subscription =
            $this->organization
                ->subscription()
                ->firstOrFail();

        $plan =
            SubscriptionPlan::query()
                ->where(
                    'slug',
                    'basic'
                )
                ->firstOrFail();

        $subscription->update([
            'subscription_plan_id' =>
                $plan->id,

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

        $this
            ->get(
                route(
                    'consent-templates.manage'
                )
            )
            ->assertOk()
            ->assertSeeText(
                $custom->title
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

        $this
            ->get(
                route(
                    'consent-templates.archived'
                )
            )
            ->assertOk()
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
}
