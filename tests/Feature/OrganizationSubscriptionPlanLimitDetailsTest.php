<?php

namespace Tests\Feature;

use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationSubscriptionPlanLimitDetailsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $organizationAdmin;

    private OrganizationSubscription $subscription;

    private SubscriptionPlan $basicPlan;

    private SubscriptionPlan $growthPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' =>
                'Plan Limit Details Clinic',

            'name' =>
                'Plan Limit Details Administrator',

            'email' =>
                'plan-limit-details@example.com',

            'password' =>
                'Password123!',

            'password_confirmation' =>
                'Password123!',
        ]);

        $this->verifyAuthenticatedUser();

        $this->organization =
            Organization::query()
                ->where(
                    'name',
                    'Plan Limit Details Clinic'
                )
                ->firstOrFail();

        $this->organizationAdmin =
            User::query()
                ->where(
                    'email',
                    'plan-limit-details@example.com'
                )
                ->firstOrFail();

        $this->subscription =
            $this
                ->organization
                ->subscription()
                ->firstOrFail();

        $this->basicPlan =
            SubscriptionPlan::query()
                ->where('slug', 'basic')
                ->firstOrFail();

        $this->growthPlan =
            SubscriptionPlan::query()
                ->where('slug', 'growth')
                ->firstOrFail();

        $this->basicPlan->update([
            'monthly_price' =>
                '10000.00',

            'currency' =>
                'KES',

            'annual_billing_enabled' =>
                true,

            'annual_discount_percent' =>
                '8.00',

            'max_consent_templates' =>
                10,

            'max_signed_consents_per_period' =>
                200,
        ]);

        $this->growthPlan->update([
            'monthly_price' =>
                '20000.00',

            'currency' =>
                'KES',

            'annual_billing_enabled' =>
                true,

            'annual_discount_percent' =>
                '10.00',

            'max_consent_templates' =>
                20,

            'max_signed_consents_per_period' =>
                400,
        ]);

        $this->subscription->update([
            'subscription_plan_id' =>
                $this->basicPlan->id,

            'billing_cycle' =>
                'monthly',

            'requires_plan_selection' =>
                false,

            'plan_selected_at' =>
                now(),
        ]);
    }

    public function test_plan_cards_show_all_configured_limits(): void
    {
        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->get(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Consent templates'
            )
            ->assertSeeText(
                'Signed consents / period'
            )
            ->assertSeeText(
                'Signed consents this period'
            )
            ->assertSeeText(
                'Archived templates do not use template capacity.'
            )
            ->assertSeeText(
                'Signed-consent usage resets each subscription billing period.'
            )
            ->assertSeeText(
                'Unlimited'
            );
    }

    public function test_plan_request_checks_template_and_signed_consent_usage(): void
    {
        $this->subscription->update([
            'subscription_plan_id' =>
                $this->growthPlan->id,

            'billing_cycle' =>
                'monthly',

            'current_period_starts_at' =>
                now()->subDay(),

            'current_period_ends_at' =>
                now()->addMonth(),
        ]);

        $this->basicPlan->update([
            'max_consent_templates' =>
                0,

            'max_signed_consents_per_period' =>
                0,
        ]);

        $template =
            $this->createPublishedTemplate();

        ConsentSession::query()->create([
            'organization_id' =>
                $this->organization->id,

            'consent_template_id' =>
                $template->id,

            'consent_template_version_id' =>
                $template->active_version_id,

            'signing_station_id' =>
                null,

            'created_by' =>
                $this->organizationAdmin->id,

            'signer_name' =>
                'Plan Limit Signer',

            'signer_email' =>
                null,

            'signer_reference' =>
                null,

            'access_token' =>
                (string) Str::uuid(),

            'status' =>
                ConsentSession::STATUS_COMPLETED,

            'responses' =>
                [],

            'started_at' =>
                now(),

            'completed_at' =>
                now(),

            'cancelled_at' =>
                null,

            'expires_at' =>
                null,

            'expired_at' =>
                null,
        ]);

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->from(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->basicPlan
                ),
                [
                    'billing_cycle' =>
                        'monthly',
                ]
            )
            ->assertRedirect(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertSessionHasErrors([
                'subscription_plan',
            ]);

        $message =
            session('errors')
                ->first(
                    'subscription_plan'
                );

        $this->assertStringContainsString(
            'consent templates',
            $message
        );

        $this->assertStringContainsString(
            'signed consents in the current billing period',
            $message
        );

        $this->assertDatabaseCount(
            'organization_subscription_plan_requests',
            0
        );

        $this->assertDatabaseCount(
            'subscription_invoices',
            0
        );
    }

    private function createPublishedTemplate(): ConsentTemplate
    {
        $template =
            ConsentTemplate::query()->create([
                'organization_id' =>
                    $this->organization->id,

                'title' =>
                    'Plan Limit Template',

                'description' =>
                    null,

                'usage_type' =>
                    ConsentTemplate::USAGE_INDIVIDUAL,

                'template_schema' =>
                    [],

                'active_version_id' =>
                    null,

                'has_unpublished_changes' =>
                    true,

                'status' =>
                    'draft',
            ]);

        $version =
            $template
                ->versions()
                ->create([
                    'version_number' =>
                        1,

                    'title' =>
                        'Plan Limit Template',

                    'description' =>
                        null,

                    'template_schema' =>
                        [],

                    'published_at' =>
                        now(),

                    'published_by' =>
                        $this->organizationAdmin->id,
                ]);

        $template->update([
            'active_version_id' =>
                $version->id,

            'has_unpublished_changes' =>
                false,

            'status' =>
                'published',
        ]);

        return $template->refresh();
    }
}
