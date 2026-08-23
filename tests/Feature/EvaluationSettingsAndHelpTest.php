<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationSettingsAndHelpTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            SubscriptionPlanSeeder::class
        );

        $this
            ->post('/register', [
                'organization_name' =>
                    'Evaluation Settings Clinic',

                'name' =>
                    'Evaluation Settings Administrator',

                'email' =>
                    'evaluation-settings@example.com',

                'password' =>
                    'StrongPass1!',

                'password_confirmation' =>
                    'StrongPass1!',
            ])
            ->assertRedirect(
                route('verification.notice')
            );

        $this->verifyAuthenticatedUser();

        $this->administrator =
            User::query()
                ->where(
                    'email',
                    'evaluation-settings@example.com'
                )
                ->firstOrFail();

        $this->organization =
            $this->administrator
                ->organization()
                ->firstOrFail();
    }

    public function test_evaluation_settings_show_getting_started_state(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route(
                    'organization-settings.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-evaluation-settings',
                false
            )
            ->assertSee(
                'data-evaluation-settings-progress="0/5"',
                false
            )
            ->assertSee(
                'data-evaluation-settings-complete="0"',
                false
            )
            ->assertSeeText(
                'Free Evaluation'
            )
            ->assertSeeText(
                'Complete your evaluation setup'
            )
            ->assertSeeText(
                '0 of 5 steps complete'
            )
            ->assertSee(
                'data-evaluation-progress-bar',
                false
            )
            ->assertSee(
                'data-evaluation-next-step="profile"',
                false
            )
            ->assertSeeText(
                'Next recommended step'
            )
            ->assertSeeText(
                'Complete organization profile'
            )
            ->assertSeeText(
                'Continue setup'
            )
            ->assertSee(
                route('dashboard')
                    .'#evaluation-getting-started',
                false
            );
    }

    public function test_paid_settings_hide_evaluation_getting_started_state(): void
    {
        $this
            ->organization
            ->subscription()
            ->firstOrFail()
            ->update([
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
            ->actingAs(
                $this->administrator
            )
            ->get(
                route(
                    'organization-settings.index'
                )
            )
            ->assertOk()
            ->assertDontSee(
                'data-evaluation-settings',
                false
            )
            ->assertDontSeeText(
                'Continue setup'
            );
    }

    public function test_dashboard_has_contextual_help(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route('dashboard')
            )
            ->assertOk()
            ->assertSee(
                'data-contextual-help',
                false
            )
            ->assertSee(
                'data-help-trigger',
                false
            )
            ->assertSee(
                'data-help-context="dashboard"',
                false
            )
            ->assertSeeText(
                'Help & tips'
            );
    }

    public function test_settings_have_settings_specific_help(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route(
                    'organization-settings.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-contextual-help',
                false
            )
            ->assertSee(
                'data-help-context="settings"',
                false
            )
            ->assertSeeText(
                'Settings help'
            );
    }

    public function test_consent_template_pages_have_template_specific_help(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route(
                    'consent-templates.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-contextual-help',
                false
            )
            ->assertSee(
                'data-help-context="templates"',
                false
            )
            ->assertSeeText(
                'Consent template help'
            );
    }

    public function test_signing_station_pages_have_station_specific_help(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route(
                    'signing-stations.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-contextual-help',
                false
            )
            ->assertSee(
                'data-help-context="signing-stations"',
                false
            )
            ->assertSeeText(
                'Signing station help'
            );
    }

    public function test_consent_record_pages_have_record_specific_help(): void
    {
        $this
            ->actingAs(
                $this->administrator
            )
            ->get(
                route(
                    'consent-sessions.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-contextual-help',
                false
            )
            ->assertSee(
                'data-help-context="consent-records"',
                false
            )
            ->assertSeeText(
                'Consent record help'
            );
    }
}
