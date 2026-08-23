<?php

namespace Tests\Feature;

use App\Models\ConsentCampaign;
use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContextualHelpRenderedPagesTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $user;

    private ConsentTemplate $template;

    private ConsentSession $session;

    private SigningStation $station;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->organization =
            Organization::query()->create([
                'name' =>
                    'Rendered Help Organization',

                'slug' =>
                    'rendered-help-organization',
            ]);

        $this->user =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'is_active' =>
                    true,
            ]);

        $this->enablePaidOrganizationAccess(
            $this->organization,
            $this->user
        );

        $this->template =
            $this->createPublishedTemplate();

        $this->session =
            $this->createConsentSession();

        $this->station =
            $this->createSigningStation();

        $this->actingAs(
            $this->user
        );
    }

    public function test_template_pages_render_their_exact_help_contexts(): void
    {
        $pages = [
            [
                route(
                    'consent-templates.index'
                ),
                'templates',
            ],
            [
                route(
                    'consent-templates.archived'
                ),
                'templates-archived',
            ],
            [
                route(
                    'consent-templates.create'
                ),
                'templates-create',
            ],
            [
                route(
                    'consent-templates.manage'
                ),
                'templates-manage',
            ],
            [
                route(
                    'consent-templates.edit',
                    $this->template
                ),
                'templates-edit',
            ],
            [
                route(
                    'consent-templates.preview',
                    $this->template
                ),
                'templates-preview',
            ],
            [
                route(
                    'consent-templates.published',
                    $this->template
                ),
                'templates-published',
            ],
            [
                route(
                    'consent-templates.history',
                    $this->template
                ),
                'templates-history',
            ],
        ];

        foreach (
            $pages
            as [$url, $context]
        ) {
            $this
                ->get($url)
                ->assertOk()
                ->assertSee(
                    'data-contextual-help',
                    false
                )
                ->assertSee(
                    'data-help-context="' .
                    $context .
                    '"',
                    false
                )
                ->assertDontSee(
                    'data-help-context="general"',
                    false
                );
        }
    }

    public function test_consent_record_pages_render_their_exact_help_contexts(): void
    {
        $pages = [
            [
                route(
                    'consent-sessions.index'
                ),
                'consent-records',
            ],
            [
                route(
                    'consent-sessions.export-data'
                ),
                'consent-export-data',
            ],
            [
                route(
                    'consent-sessions.show',
                    $this->session
                ),
                'consent-record',
            ],
            [
                route(
                    'consent-sessions.audit',
                    $this->session
                ),
                'consent-audit',
            ],
        ];

        foreach (
            $pages
            as [$url, $context]
        ) {
            $this
                ->get($url)
                ->assertOk()
                ->assertSee(
                    'data-help-context="' .
                    $context .
                    '"',
                    false
                );
        }
    }

    public function test_campaign_pages_render_their_exact_help_contexts(): void
    {
        $this
            ->get(
                route(
                    'consent-campaigns.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="campaigns"',
                false
            );

        $this
            ->get(
                route(
                    'consent-campaigns.select-template'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="campaign-select-template"',
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
            ->assertSee(
                'data-help-context="campaign-create"',
                false
            )
            ->assertSeeText(
                'A campaign accepts between 1 and 20 recipients'
            )
            ->assertSee(
                'data-help-section-type="warning"',
                false
            );

        $this
            ->post(
                route(
                    'consent-campaigns.store',
                    $this->template
                ),
                [
                    'campaign_name' =>
                        'Rendered Help Campaign',

                    'recipients' => [
                        [
                            'name' =>
                                'Help Test Recipient',

                            'email' =>
                                'help-recipient@example.com',

                            'reference' =>
                                'HELP-001',
                        ],
                    ],
                ]
            )
            ->assertSessionDoesntHaveErrors();

        $campaign =
            ConsentCampaign::query()
                ->sole();

        $this
            ->get(
                route(
                    'consent-campaigns.show',
                    $campaign
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="campaign-show"',
                false
            );
    }

    public function test_signing_station_pages_render_their_exact_help_contexts(): void
    {
        $pages = [
            [
                route(
                    'signing-stations.index'
                ),
                'signing-stations',
                false,
            ],
            [
                route(
                    'signing-stations.create'
                ),
                'signing-stations-create',
                true,
            ],
            [
                route(
                    'signing-stations.show',
                    $this->station
                ),
                'signing-stations-show',
                true,
            ],
            [
                route(
                    'signing-stations.edit',
                    $this->station
                ),
                'signing-stations-edit',
                true,
            ],
            [
                route(
                    'signing-stations.analytics'
                ),
                'signing-stations-analytics',
                false,
            ],
        ];

        foreach (
            $pages
            as [$url, $context, $expectsWarning]
        ) {
            $response =
                $this->get($url);

            $response
                ->assertOk()
                ->assertSee(
                    'data-help-context="' .
                    $context .
                    '"',
                    false
                );

            if ($expectsWarning) {
                $response->assertSee(
                    'data-help-section-type="warning"',
                    false
                );
            }
        }
    }

    public function test_high_impact_rendered_pages_show_warning_cards(): void
    {
        $pages = [
            route(
                'consent-templates.create'
            ),

            route(
                'consent-templates.edit',
                $this->template
            ),

            route(
                'consent-templates.preview',
                $this->template
            ),

            route(
                'consent-templates.published',
                $this->template
            ),

            route(
                'consent-sessions.show',
                $this->session
            ),

            route(
                'consent-sessions.export-data'
            ),

            route(
                'consent-campaigns.create',
                $this->template
            ),

            route(
                'signing-stations.create'
            ),

            route(
                'signing-stations.show',
                $this->station
            ),

            route(
                'signing-stations.edit',
                $this->station
            ),

            route(
                'profile.edit'
            ),
        ];

        foreach ($pages as $url) {
            $this
                ->get($url)
                ->assertOk()
                ->assertSee(
                    'data-help-section-type="warning"',
                    false
                )
                ->assertSeeText(
                    'Warning'
                );
        }
    }

    public function test_export_help_explains_data_scope_and_pdf_separation(): void
    {
        $this
            ->get(
                route(
                    'consent-sessions.export-data'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="consent-export-data"',
                false
            )
            ->assertSeeText(
                'Choosing export columns'
            )
            ->assertSeeText(
                'Only columns containing data somewhere in the current matching record scope are offered.'
            )
            ->assertSeeText(
                'PDF download of signed consent documents remains separate from structured data export.'
            )
            ->assertSeeText(
                'Personal information'
            )
            ->assertSee(
                'data-help-section-type="warning"',
                false
            );
    }

    public function test_signing_station_edit_help_explains_qr_rotation_risk(): void
    {
        $this
            ->get(
                route(
                    'signing-stations.edit',
                    $this->station
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="signing-stations-edit"',
                false
            )
            ->assertSeeText(
                'QR and device impact'
            )
            ->assertSeeText(
                'A rotation invalidates old QR access and releases existing device leases'
            )
            ->assertSee(
                'data-help-section-type="warning"',
                false
            );
    }

    public function test_profile_renders_account_specific_help(): void
    {
        $this
            ->get(
                route(
                    'profile.edit'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-help-context="profile"',
                false
            )
            ->assertSeeText(
                'Profile & account help'
            )
            ->assertSeeText(
                'Deleting your account'
            )
            ->assertSee(
                'data-help-section-type="warning"',
                false
            );
    }

    private function createPublishedTemplate(): ConsentTemplate
    {
        $template =
            ConsentTemplate::query()->create([
                'organization_id' =>
                    $this->organization->id,

                'title' =>
                    'Rendered Help Consent',

                'description' =>
                    'Consent used to test contextual help.',

                'category' =>
                    'Testing',

                'usage_type' =>
                    ConsentTemplate::USAGE_BOTH,

                'template_schema' => [
                    'builder_version' =>
                        2,

                    'consent_html' =>
                        '<p>I consent.</p>',

                    'consent_text' =>
                        'I consent.',

                    'additional_fields' => [
                        [
                            'id' =>
                                'department',

                            'type' =>
                                'text',

                            'label' =>
                                'Department',

                            'required' =>
                                false,

                            'options' =>
                                [],
                        ],
                    ],
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
                        $template->description,

                    'template_schema' =>
                        $template->template_schema,

                    'published_at' =>
                        now(),

                    'published_by' =>
                        $this->user->id,
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

    private function createConsentSession(): ConsentSession
    {
        return ConsentSession::query()->create([
            'organization_id' =>
                $this->organization->id,

            'consent_template_id' =>
                $this->template->id,

            'consent_template_version_id' =>
                $this->template
                    ->active_version_id,

            'signing_station_id' =>
                null,

            'consent_campaign_id' =>
                null,

            'created_by' =>
                $this->user->id,

            'signer_name' =>
                'Rendered Help Signer',

            'signer_email' =>
                'rendered-help-signer@example.com',

            'signer_reference' =>
                'HELP-SIGNER-001',

            'access_token' =>
                (string) Str::uuid(),

            'status' =>
                ConsentSession::STATUS_PENDING,

            'responses' => [
                'department' =>
                    'Operations',
            ],
        ]);
    }

    private function createSigningStation(): SigningStation
    {
        return SigningStation::query()->create([
            'organization_id' =>
                $this->organization->id,

            'consent_template_id' =>
                $this->template->id,

            'created_by' =>
                $this->user->id,

            'name' =>
                'Rendered Help Station',

            'station_token' =>
                (string) Str::uuid(),

            'active' =>
                true,

            'require_email' =>
                false,

            'require_reference' =>
                false,

            'auto_reset_seconds' =>
                3,
        ]);
    }
}
