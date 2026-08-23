<?php

namespace Tests\Feature;

use App\Models\ConsentCampaign;
use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EvaluationBulkCampaignPaywallTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private User $administrator;
    private ConsentTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            SubscriptionPlanSeeder::class
        );

        $this->post('/register', [
            'organization_name' =>
                'Evaluation Bulk Paywall Clinic',

            'name' =>
                'Evaluation Administrator',

            'email' =>
                'evaluation-bulk-paywall@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->verifyAuthenticatedUser();

        $this->organization =
            Organization::query()
                ->where(
                    'name',
                    'Evaluation Bulk Paywall Clinic'
                )
                ->firstOrFail();

        $this->administrator =
            User::query()
                ->where(
                    'email',
                    'evaluation-bulk-paywall@example.com'
                )
                ->firstOrFail();

        $this->template =
            ConsentTemplate::query()
                ->create([
                    'organization_id' =>
                        $this->organization->id,

                    'title' =>
                        'Evaluation Bulk Template',

                    'description' =>
                        'Bulk paywall test.',

                    'category' =>
                        'Testing',

                    'usage_type' =>
                        ConsentTemplate::
                            USAGE_INDIVIDUAL,

                    'template_schema' => [
                        'sections' => [],
                    ],

                    'active_version_id' =>
                        null,

                    'has_unpublished_changes' =>
                        false,

                    'status' =>
                        'draft',
                ]);

        $version =
            $this->template
                ->versions()
                ->create([
                    'version_number' =>
                        1,

                    'title' =>
                        $this->template->title,

                    'description' =>
                        $this->template
                            ->description,

                    'template_schema' =>
                        $this->template
                            ->template_schema,

                    'published_at' =>
                        now(),

                    'published_by' =>
                        $this->administrator->id,
                ]);

        $this->template->update([
            'active_version_id' =>
                $version->id,

            'has_unpublished_changes' =>
                false,

            'status' =>
                'published',
        ]);

        $this->template =
            $this->template->refresh();

        $this->actingAs(
            $this->administrator
        );
    }

    public function test_evaluation_bulk_entry_page_shows_paid_feature_upgrade_screen(): void
    {
        $this
            ->get(
                route(
                    'consent-campaigns.select-template'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Bulk campaigns are available on paid plans'
            )
            ->assertSeeText(
                'Send up to 5 lifetime individual signing emails.'
            )
            ->assertSeeText(
                'View subscription plans'
            )
            ->assertSee(
                route(
                    'organization-subscription-plans.index'
                ),
                false
            );
    }

    public function test_evaluation_cannot_open_bulk_campaign_creation_directly(): void
    {
        $this
            ->get(
                route(
                    'consent-campaigns.create',
                    $this->template
                )
            )
            ->assertRedirect(
                route(
                    'consent-campaigns.select-template'
                )
            );
    }

    public function test_evaluation_cannot_submit_bulk_campaign_directly(): void
    {
        Mail::fake();

        $this
            ->post(
                route(
                    'consent-campaigns.store',
                    $this->template
                ),
                [
                    'campaign_name' =>
                        'Blocked Evaluation Campaign',

                    'recipients' => [
                        [
                            'name' =>
                                'Blocked Recipient',

                            'email' =>
                                'blocked@example.com',

                            'reference' =>
                                'BLOCKED-1',
                        ],
                    ],
                ]
            )
            ->assertRedirect(
                route(
                    'consent-campaigns.select-template'
                )
            );

        $this->assertSame(
            0,
            ConsentCampaign::query()->count()
        );

        $this->assertSame(
            0,
            ConsentSession::query()->count()
        );

        Mail::assertNothingSent();
    }
}
