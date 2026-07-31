<?php

namespace Tests\Feature;

use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionUsageLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionConsentUsageLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_limit_counts_only_non_archived_templates(): void
    {
        [$organization] = $this->subscriptionContext(
            templateLimit: 1,
            signedLimit: null
        );

        $template = $this->createTemplate(
            $organization,
            'Active template'
        );

        $this->expectException(
            ValidationException::class
        );

        DB::transaction(function () use (
            $organization
        ): void {
            app(
                SubscriptionUsageLimitService::class
            )->assertTemplateSlotAvailableLocked(
                $organization->id
            );
        });

        $template->update([
            'status' => 'archived',
        ]);
    }

    public function test_archiving_a_template_releases_its_slot(): void
    {
        [$organization] = $this->subscriptionContext(
            templateLimit: 1,
            signedLimit: null
        );

        $template = $this->createTemplate(
            $organization,
            'Archived template'
        );

        $template->update([
            'status' => 'archived',
        ]);

        DB::transaction(function () use (
            $organization
        ): void {
            app(
                SubscriptionUsageLimitService::class
            )->assertTemplateSlotAvailableLocked(
                $organization->id
            );
        });

        $this->assertTrue(true);
    }

    public function test_signed_limit_counts_only_the_current_subscription_period(): void
    {
        [
            $organization,
            $subscription,
        ] = $this->subscriptionContext(
            templateLimit: null,
            signedLimit: 1
        );

        $template = $this->createTemplate(
            $organization,
            'Signing limit template'
        );

        $this->createCompletedSession(
            $organization,
            $template,
            '2026-07-10 12:00:00'
        );

        $this->createCompletedSession(
            $organization,
            $template,
            '2026-06-20 12:00:00'
        );

        try {
            DB::transaction(function () use (
                $organization
            ): void {
                app(
                    SubscriptionUsageLimitService::class
                )->assertSignedConsentSlotAvailableLocked(
                    $organization->id
                );
            });

            $this->fail(
                'The current-period signed-consent limit was not enforced.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'subscription',
                $exception->errors()
            );
        }

        $subscription->update([
            'current_period_starts_at' =>
                '2026-08-01 00:00:00',

            'current_period_ends_at' =>
                '2026-09-01 00:00:00',
        ]);

        DB::transaction(function () use (
            $organization
        ): void {
            app(
                SubscriptionUsageLimitService::class
            )->assertSignedConsentSlotAvailableLocked(
                $organization->id
            );
        });

        $this->assertTrue(true);
    }

    public function test_null_limits_are_unlimited_and_zero_limits_disable_usage(): void
    {
        [
            $unlimitedOrganization,
        ] = $this->subscriptionContext(
            templateLimit: null,
            signedLimit: null
        );

        DB::transaction(function () use (
            $unlimitedOrganization
        ): void {
            $service = app(
                SubscriptionUsageLimitService::class
            );

            $service->assertTemplateSlotAvailableLocked(
                $unlimitedOrganization->id
            );

            $service->assertSignedConsentSlotAvailableLocked(
                $unlimitedOrganization->id
            );
        });

        [
            $disabledOrganization,
        ] = $this->subscriptionContext(
            templateLimit: 0,
            signedLimit: 0
        );

        $this->expectException(
            ValidationException::class
        );

        DB::transaction(function () use (
            $disabledOrganization
        ): void {
            app(
                SubscriptionUsageLimitService::class
            )->assertTemplateSlotAvailableLocked(
                $disabledOrganization->id
            );
        });
    }

    /**
     * @return array{0: Organization, 1: OrganizationSubscription}
     */
    private function subscriptionContext(
        ?int $templateLimit,
        ?int $signedLimit
    ): array {
        $organization = Organization::query()->create([
            'name' =>
                'Usage Limit Organization '.Str::random(8),

            'slug' =>
                'usage-limit-'.Str::lower(
                    Str::random(12)
                ),
        ]);

        $plan = SubscriptionPlan::query()->create([
            'name' =>
                'Usage Limit Plan '.Str::random(8),

            'slug' =>
                'usage-limit-plan-'.Str::lower(
                    Str::random(12)
                ),

            'description' =>
                'Plan used by consent usage-limit tests.',

            'monthly_price' =>
                '1000.00',

            'currency' =>
                'KES',

            'annual_billing_enabled' =>
                false,

            'annual_discount_percent' =>
                '0.00',

            'max_users' =>
                10,

            'max_consent_managers' =>
                2,

            'max_staff' =>
                5,

            'max_auditors' =>
                2,

            'max_active_kiosks' =>
                2,

            'max_consent_templates' =>
                $templateLimit,

            'max_signed_consents_per_period' =>
                $signedLimit,

            'is_active' =>
                true,

            'sort_order' =>
                1,
        ]);

        $subscription =
            OrganizationSubscription::query()->create([
                'organization_id' =>
                    $organization->id,

                'subscription_plan_id' =>
                    $plan->id,

                'status' =>
                    'active',

                'payment_status' =>
                    'paid',

                'billing_cycle' =>
                    'monthly',

                'starts_at' =>
                    '2026-07-01 00:00:00',

                'current_period_starts_at' =>
                    '2026-07-01 00:00:00',

                'current_period_ends_at' =>
                    '2026-08-01 00:00:00',
            ]);

        return [
            $organization,
            $subscription,
        ];
    }

    private function createTemplate(
        Organization $organization,
        string $title
    ): ConsentTemplate {
        $publisher = User::factory()->create([
            'organization_id' =>
                $organization->id,
        ]);

        $template = ConsentTemplate::query()->create([
            'organization_id' =>
                $organization->id,

            'title' =>
                $title,

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

        $version = $template
            ->versions()
            ->create([
                'version_number' =>
                    1,

                'title' =>
                    $title,

                'description' =>
                    null,

                'template_schema' =>
                    [],

                'published_at' =>
                    now(),

                'published_by' =>
                    $publisher->id,
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

    private function createCompletedSession(
        Organization $organization,
        ConsentTemplate $template,
        string $completedAt
    ): ConsentSession {
        return ConsentSession::query()->create([
            'organization_id' =>
                $organization->id,

            'consent_template_id' =>
                $template->id,

            'consent_template_version_id' =>
                $template->active_version_id,

            'signing_station_id' =>
                null,

            'created_by' =>
                $template
                    ->versions()
                    ->whereKey(
                        $template->active_version_id
                    )
                    ->value('published_by'),

            'signer_name' =>
                'Usage Limit Signer',

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
                $completedAt,

            'completed_at' =>
                $completedAt,

            'cancelled_at' =>
                null,

            'expires_at' =>
                null,

            'expired_at' =>
                null,
        ]);
    }
}
