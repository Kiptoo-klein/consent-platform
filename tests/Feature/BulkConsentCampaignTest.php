<?php

namespace Tests\Feature;

use App\Mail\ConsentSigningRequestMail;
use App\Models\ConsentCampaign;
use App\Models\ConsentNotification;
use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class BulkConsentCampaignTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private User $administrator;
    private ConsentTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization =
            Organization::query()->create([
                'name' =>
                    'Bulk Consent Clinic',

                'slug' =>
                    'bulk-consent-clinic',
            ]);

        $this->administrator =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'is_active' =>
                    true,
            ]);

        $this->enablePaidOrganizationAccess(
            $this->organization,
            $this->administrator
        );

        $this->template =
            $this->createPublishedTemplate(
                $this->organization,
                $this->administrator,
                'Staff NDA'
            );

        $this->actingAs(
            $this->administrator
        );
    }

    public function test_new_consent_redirects_to_generic_builder_and_bulk_flow_remains_available(): void
    {
        $this
            ->get(
                route(
                    'consent-templates.new'
                )
            )
            ->assertRedirect(
                route(
                    'consent-templates.create'
                )
            );

        $this
            ->get(
                route(
                    'consent-templates.create'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'New Consent'
            )
            ->assertDontSeeText(
                'New Individual Consent'
            )
            ->assertDontSeeText(
                'New Public Consent'
            )
            ->assertDontSeeText(
                'Selected workflow'
            );

        $this->assertSame(
            '/consent-campaigns/create',
            route(
                'consent-campaigns.select-template',
                [],
                false
            )
        );

        $templateResponse = $this->get(
            route(
                'consent-campaigns.select-template'
            )
        );

        $templateResponse
            ->assertOk()
            ->assertSeeText(
                'New Bulk Consent'
            )
            ->assertSeeText(
                'Staff NDA'
            )
            ->assertSeeText(
                'Use for Bulk Consent'
            )
            ->assertSee(
                route(
                    'consent-campaigns.create',
                    $this->template
                ),
                false
            );

        $this
            ->get(
                route(
                    'consent-campaigns.create',
                    $this->template
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Send to Multiple People'
            )
            ->assertSeeText(
                'Maximum 20'
            )
            ->assertSeeText(
                'Each person receives a different secure signing link by email.'
            );
    }


    public function test_campaign_accepts_one_recipient_because_twenty_is_only_the_maximum(): void
    {
        Mail::fake();

        $response = $this->post(
            route(
                'consent-campaigns.store',
                $this->template
            ),
            [
                'campaign_name' =>
                    'Single Person Campaign',

                'recipients' => [
                    [
                        'name' =>
                            'Only Recipient',

                        'email' =>
                            'only@example.com',

                        'reference' =>
                            'EMP-ONE',
                    ],
                ],
            ]
        );

        $campaign =
            ConsentCampaign::query()
                ->sole();

        $response
            ->assertRedirect(
                route(
                    'consent-campaigns.show',
                    $campaign
                )
            )
            ->assertSessionHas(
                'success'
            );

        $this->assertSame(
            1,
            $campaign->recipient_count
        );

        $this->assertDatabaseHas(
            'consent_sessions',
            [
                'consent_campaign_id' =>
                    $campaign->id,

                'signer_email' =>
                    'only@example.com',
            ]
        );

        Mail::assertSent(
            ConsentSigningRequestMail::class,
            1
        );
    }


    public function test_campaign_can_be_created_without_a_deadline(): void
    {
        Mail::fake();

        $response = $this->post(
            route(
                'consent-campaigns.store',
                $this->template
            ),
            [
                'campaign_name' =>
                    'No Deadline Campaign',

                'recipients' => [
                    [
                        'name' =>
                            'No Deadline Recipient',

                        'email' =>
                            'no-deadline@example.com',

                        'reference' =>
                            null,
                    ],
                ],

                'expires_date' =>
                    null,

                'expires_time' =>
                    null,
            ]
        );

        $campaign =
            ConsentCampaign::query()
                ->sole();

        $response
            ->assertRedirect(
                route(
                    'consent-campaigns.show',
                    $campaign
                )
            )
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(
            $campaign->expires_at
        );

        $this->assertNull(
            $campaign
                ->consentSessions()
                ->sole()
                ->expires_at
        );
    }

    public function test_deadline_time_is_rejected_without_a_date(): void
    {
        Mail::fake();

        $this
            ->from(
                route(
                    'consent-campaigns.create',
                    $this->template
                )
            )
            ->post(
                route(
                    'consent-campaigns.store',
                    $this->template
                ),
                [
                    'campaign_name' =>
                        'Invalid Deadline Campaign',

                    'recipients' => [
                        [
                            'name' =>
                                'Invalid Deadline Recipient',

                            'email' =>
                                'invalid-deadline@example.com',

                            'reference' =>
                                null,
                        ],
                    ],

                    'expires_date' =>
                        null,

                    'expires_time' =>
                        '09:30',
                ]
            )
            ->assertRedirect(
                route(
                    'consent-campaigns.create',
                    $this->template
                )
            )
            ->assertSessionHasErrors(
                'expires_time'
            );

        $this->assertDatabaseCount(
            'consent_campaigns',
            0
        );
    }

    public function test_legacy_signing_station_template_can_be_used_for_bulk(): void
    {
        $this->template->update([
            'usage_type' =>
                ConsentTemplate::USAGE_SIGNING_STATION,
        ]);

        $this
            ->get(
                route(
                    'consent-campaigns.select-template'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Staff NDA'
            )
            ->assertSee(
                route(
                    'consent-campaigns.create',
                    $this->template
                ),
                false
            );

        $this
            ->get(
                route(
                    'consent-campaigns.create',
                    $this->template
                )
            )
            ->assertOk();
    }

    public function test_campaign_creates_and_emails_one_independent_record_per_recipient(): void
    {
        Mail::fake();

        $response = $this->post(
            route(
                'consent-campaigns.store',
                $this->template
            ),
            [
                'campaign_name' =>
                    'August Staff NDA',

                'recipients' => [
                    [
                        'name' =>
                            'Alice Example',

                        'email' =>
                            'alice@example.com',

                        'reference' =>
                            'EMP-001',
                    ],

                    [
                        'name' =>
                            'Bob Example',

                        'email' =>
                            'bob@example.com',

                        'reference' =>
                            'EMP-002',
                    ],
                ],
            ]
        );

        $campaign =
            ConsentCampaign::query()
                ->sole();

        $response
            ->assertRedirect(
                route(
                    'consent-campaigns.show',
                    $campaign
                )
            )
            ->assertSessionHas(
                'success'
            );

        $this->assertSame(
            2,
            $campaign->recipient_count
        );

        $sessions =
            ConsentSession::query()
                ->where(
                    'consent_campaign_id',
                    $campaign->id
                )
                ->orderBy('id')
                ->get();

        $this->assertCount(
            2,
            $sessions
        );

        $this->assertSame(
            2,
            $sessions
                ->pluck('access_token')
                ->unique()
                ->count()
        );

        $this->assertSame(
            [
                'alice@example.com',
                'bob@example.com',
            ],
            $sessions
                ->pluck('signer_email')
                ->all()
        );

        $this->assertDatabaseCount(
            'consent_notifications',
            2
        );

        $this->assertSame(
            2,
            ConsentNotification::query()
                ->where(
                    'status',
                    ConsentNotification::
                        STATUS_SENT
                )
                ->count()
        );

        Mail::assertSent(
            ConsentSigningRequestMail::class,
            2
        );

        foreach ($sessions as $session) {
            $this->assertDatabaseHas(
                'consent_audit_events',
                [
                    'consent_session_id' =>
                        $session->id,

                    'event_type' =>
                        'consent.created',
                ]
            );
        }
    }

    public function test_campaign_rejects_more_than_twenty_and_duplicate_email_addresses(): void
    {
        $tooManyRecipients =
            collect(
                range(1, 21)
            )
                ->map(
                    fn (int $index): array => [
                        'name' =>
                            "Recipient {$index}",

                        'email' =>
                            "recipient{$index}@example.com",

                        'reference' =>
                            null,
                    ]
                )
                ->all();

        $this
            ->post(
                route(
                    'consent-campaigns.store',
                    $this->template
                ),
                [
                    'campaign_name' =>
                        'Too Large',

                    'recipients' =>
                        $tooManyRecipients,
                ]
            )
            ->assertSessionHasErrors(
                'recipients'
            );

        $this->assertDatabaseCount(
            'consent_campaigns',
            0
        );

        $this
            ->post(
                route(
                    'consent-campaigns.store',
                    $this->template
                ),
                [
                    'campaign_name' =>
                        'Duplicate Emails',

                    'recipients' => [
                        [
                            'name' =>
                                'First Person',

                            'email' =>
                                'same@example.com',

                            'reference' =>
                                null,
                        ],

                        [
                            'name' =>
                                'Second Person',

                            'email' =>
                                'SAME@example.com',

                            'reference' =>
                                null,
                        ],
                    ],
                ]
            )
            ->assertSessionHasErrors(
                'recipients'
            );

        $this->assertDatabaseCount(
            'consent_campaigns',
            0
        );
    }

    public function test_csv_recipients_are_imported_and_sent(): void
    {
        Mail::fake();

        $file =
            UploadedFile::fake()
                ->createWithContent(
                    'nda-recipients.csv',
                    "name,email,reference\n"
                    ."Carol Example,carol@example.com,EMP-003\n"
                    ."David Example,david@example.com,EMP-004\n"
                );

        $this
            ->post(
                route(
                    'consent-campaigns.store',
                    $this->template
                ),
                [
                    'campaign_name' =>
                        'CSV Staff NDA',

                    'recipient_file' =>
                        $file,
                ]
            )
            ->assertSessionHas(
                'success'
            );

        $campaign =
            ConsentCampaign::query()
                ->sole();

        $this->assertSame(
            2,
            $campaign->recipient_count
        );

        $this->assertDatabaseHas(
            'consent_sessions',
            [
                'consent_campaign_id' =>
                    $campaign->id,

                'signer_name' =>
                    'Carol Example',

                'signer_email' =>
                    'carol@example.com',

                'signer_reference' =>
                    'EMP-003',
            ]
        );

        $this->assertDatabaseHas(
            'consent_sessions',
            [
                'consent_campaign_id' =>
                    $campaign->id,

                'signer_name' =>
                    'David Example',

                'signer_email' =>
                    'david@example.com',

                'signer_reference' =>
                    'EMP-004',
            ]
        );

        Mail::assertSent(
            ConsentSigningRequestMail::class,
            2
        );
    }


    public function test_bulk_template_chooser_offers_create_new_template(): void
    {
        $response = $this->get(
            route(
                'consent-campaigns.select-template'
            )
        );

        $response
            ->assertOk()
            ->assertSeeText(
                'Use Existing Template'
            )
            ->assertSeeText(
                'Create New Template'
            )
            ->assertSee(
                route(
                    'consent-templates.create',
                    [
                        'return_to' =>
                            'bulk',
                    ]
                )
            );
    }

    public function test_new_bulk_template_is_published_and_continues_to_recipients(): void
    {
        $response = $this->post(
            route(
                'consent-templates.store'
            ),
            [
                'title' =>
                    'Fresh Bulk Consent',

                'description' =>
                    'Created directly from the bulk flow.',

                'content' =>
                    'I consent to the stated terms.',

                'additional_fields_json' =>
                    '[]',

                'return_to' =>
                    'bulk',
            ]
        );

        $createdTemplate =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->where(
                    'title',
                    'Fresh Bulk Consent'
                )
                ->firstOrFail();

        $response
            ->assertRedirect(
                route(
                    'consent-campaigns.create',
                    $createdTemplate
                )
            )
            ->assertSessionHas(
                'success',
                'Template created and published. Add the campaign recipients.'
            );

        $this->assertSame(
            ConsentTemplate::USAGE_BOTH,
            $createdTemplate->usage_type
        );

        $this->assertSame(
            'published',
            $createdTemplate->status
        );

        $this->assertFalse(
            (bool) $createdTemplate
                ->has_unpublished_changes
        );

        $this->assertNotNull(
            $createdTemplate
                ->active_version_id
        );

        $this->assertDatabaseHas(
            'consent_template_versions',
            [
                'consent_template_id' =>
                    $createdTemplate->id,

                'version_number' =>
                    1,

                'published_by' =>
                    $this->administrator->id,
            ]
        );

        $this
            ->get(
                route(
                    'consent-campaigns.create',
                    $createdTemplate
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Send to Multiple People'
            )
            ->assertSeeText(
                'Fresh Bulk Consent'
            );
    }


    public function test_campaigns_and_templates_are_tenant_scoped(): void
    {
        $otherOrganization =
            Organization::query()->create([
                'name' =>
                    'Other Bulk Clinic',

                'slug' =>
                    'other-bulk-clinic',
            ]);

        $otherUser =
            User::factory()->create([
                'organization_id' =>
                    $otherOrganization->id,

                'is_active' =>
                    true,
            ]);

        $otherTemplate =
            $this->createPublishedTemplate(
                $otherOrganization,
                $otherUser,
                'Other NDA'
            );

        $this
            ->get(
                route(
                    'consent-campaigns.create',
                    $otherTemplate
                )
            )
            ->assertForbidden();

        $campaign =
            ConsentCampaign::query()->create([
                'organization_id' =>
                    $otherOrganization->id,

                'consent_template_id' =>
                    $otherTemplate->id,

                'consent_template_version_id' =>
                    $otherTemplate
                        ->active_version_id,

                'created_by' =>
                    $otherUser->id,

                'name' =>
                    'Private Other Campaign',

                'recipient_count' =>
                    1,

                'expires_at' =>
                    null,
            ]);

        $this
            ->get(
                route(
                    'consent-campaigns.show',
                    $campaign
                )
            )
            ->assertForbidden();
    }

    private function createPublishedTemplate(
        Organization $organization,
        User $publisher,
        string $title
    ): ConsentTemplate {
        $template =
            ConsentTemplate::query()->create([
                'organization_id' =>
                    $organization->id,

                'title' =>
                    $title,

                'description' =>
                    'Bulk consent campaign test template.',

                'category' =>
                    'Legal',

                'usage_type' =>
                    ConsentTemplate::
                        USAGE_INDIVIDUAL,

                'template_schema' => [
                    'sections' =>
                        [],
                ],

                'active_version_id' =>
                    null,

                'has_unpublished_changes' =>
                    false,

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
                        $template->title,

                    'description' =>
                        $template
                            ->description,

                    'template_schema' =>
                        $template
                            ->template_schema,

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
}
