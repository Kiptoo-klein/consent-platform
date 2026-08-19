<?php

namespace Tests\Feature;

use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsentDataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_data_shows_only_populated_columns_and_defaults_name_email_and_phone(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser(
                'Export Organization',
                'export-organization'
            );

        $template =
            $this->createPublishedTemplate(
                organization: $organization,
                user: $user,
                title: 'Employee Consent',
                fields: [
                    [
                        'id' =>
                            'mobile-number',

                        'type' =>
                            'phone',

                        'label' =>
                            'Mobile number',

                        'required' =>
                            false,

                        'options' =>
                            [],
                    ],
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
                    [
                        'id' =>
                            'unused-field',

                        'type' =>
                            'text',

                        'label' =>
                            'Unused field',

                        'required' =>
                            false,

                        'options' =>
                            [],
                    ],
                ]
            );

        $this->createConsentSession(
            organization: $organization,
            user: $user,
            template: $template,
            signerName: 'Jane Example',
            signerEmail: 'jane@example.com',
            responses: [
                'mobile-number' =>
                    '+254 700 111 222',

                'department' =>
                    'Operations',

                'unused-field' =>
                    '',
            ]
        );

        $response =
            $this
                ->actingAs($user)
                ->get(
                    route(
                        'consent-sessions.export-data',
                        [
                            'template_id' =>
                                $template->id,
                        ]
                    )
                );

        $response
            ->assertOk()
            ->assertViewHas(
                'matchingCount',
                1
            )
            ->assertSeeText(
                'Export data'
            )
            ->assertSeeText(
                'Name'
            )
            ->assertSeeText(
                'Email'
            )
            ->assertSeeText(
                'Mobile number'
            )
            ->assertSeeText(
                'Department'
            )
            ->assertDontSeeText(
                'Unused field'
            );

        $response->assertViewHas(
            'signerColumns',
            function ($columns): bool {
                return $columns
                    ->pluck('label')
                    ->all()
                    === [
                        'Name',
                        'Email',
                        'Mobile number',
                    ]
                    && $columns->every(
                        fn (array $column): bool =>
                            $column[
                                'selected_by_default'
                            ] === true
                    );
            }
        );

        $response->assertViewHas(
            'questionColumns',
            function ($columns): bool {
                $department =
                    $columns->firstWhere(
                        'label',
                        'Department'
                    );

                $unused =
                    $columns->firstWhere(
                        'label',
                        'Unused field'
                    );

                return $columns
                    ->pluck('label')
                    ->all()
                    === [
                        'Department',
                    ]
                    && is_array($department)
                    && $department[
                        'selected_by_default'
                    ] === false
                    && $unused === null;
            }
        );
    }

    public function test_export_data_hides_email_phone_and_questions_when_no_matching_record_contains_them(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser(
                'Sparse Export Organization',
                'sparse-export-organization'
            );

        $template =
            $this->createPublishedTemplate(
                organization: $organization,
                user: $user,
                title: 'Sparse Consent',
                fields: [
                    [
                        'id' =>
                            'phone',

                        'type' =>
                            'phone',

                        'label' =>
                            'Phone number',

                        'required' =>
                            false,

                        'options' =>
                            [],
                    ],
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
                ]
            );

        $this->createConsentSession(
            organization: $organization,
            user: $user,
            template: $template,
            signerName: 'No Contact Signer',
            signerEmail: null,
            responses: [
                'phone' =>
                    '',

                'department' =>
                    '',
            ]
        );

        $response =
            $this
                ->actingAs($user)
                ->get(
                    route(
                        'consent-sessions.export-data',
                        [
                            'template_id' =>
                                $template->id,
                        ]
                    )
                );

        $response
            ->assertOk()
            ->assertSeeText(
                'Name'
            )
            ->assertDontSeeText(
                'Email'
            )
            ->assertDontSeeText(
                'Phone number'
            )
            ->assertDontSeeText(
                'Department'
            );

        $response->assertViewHas(
            'signerColumns',
            fn ($columns): bool =>
                $columns->pluck('key')->all()
                === [
                    'signer_name',
                ]
        );

        $response->assertViewHas(
            'questionColumns',
            fn ($columns): bool =>
                $columns->isEmpty()
        );
    }

    public function test_template_filter_limits_column_discovery_to_that_consent(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser(
                'Filtered Export Organization',
                'filtered-export-organization'
            );

        $employeeTemplate =
            $this->createPublishedTemplate(
                organization: $organization,
                user: $user,
                title: 'Employee Consent',
                fields: [
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
                ]
            );

        $eventTemplate =
            $this->createPublishedTemplate(
                organization: $organization,
                user: $user,
                title: 'Event Consent',
                fields: [
                    [
                        'id' =>
                            'event-name',

                        'type' =>
                            'text',

                        'label' =>
                            'Event name',

                        'required' =>
                            false,

                        'options' =>
                            [],
                    ],
                ]
            );

        $this->createConsentSession(
            organization: $organization,
            user: $user,
            template: $employeeTemplate,
            signerName: 'Employee Signer',
            signerEmail: 'employee@example.com',
            responses: [
                'department' =>
                    'Finance',
            ]
        );

        $this->createConsentSession(
            organization: $organization,
            user: $user,
            template: $eventTemplate,
            signerName: 'Event Signer',
            signerEmail: 'event@example.com',
            responses: [
                'event-name' =>
                    'Annual Meeting',
            ]
        );

        $response =
            $this
                ->actingAs($user)
                ->get(
                    route(
                        'consent-sessions.export-data',
                        [
                            'template_id' =>
                                $employeeTemplate->id,
                        ]
                    )
                );

        $response
            ->assertOk()
            ->assertViewHas(
                'matchingCount',
                1
            )
            ->assertSeeText(
                'Employee Consent'
            )
            ->assertSeeText(
                'Department'
            )
            ->assertDontSeeText(
                'Event name'
            );

        $response->assertViewHas(
            'questionColumns',
            function ($columns): bool {
                return $columns
                    ->pluck('label')
                    ->all()
                    === [
                        'Department',
                    ];
            }
        );
    }

    public function test_consent_records_keeps_pdf_download_and_data_export_as_separate_actions(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser(
                'Records Actions Organization',
                'records-actions-organization'
            );

        $template =
            $this->createPublishedTemplate(
                organization: $organization,
                user: $user,
                title: 'Records Actions Consent',
                fields: []
            );

        $session =
            $this->createConsentSession(
                organization: $organization,
                user: $user,
                template: $template,
                signerName: 'PDF Signer',
                signerEmail: 'pdf@example.com',
                responses: []
            );

        $session->forceFill([
            'pdf_path' =>
                'consent-pdfs/example.pdf',

            'pdf_generated_at' =>
                now(),
        ])->save();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'consent-sessions.index',
                    [
                        'template_id' =>
                            $template->id,
                    ]
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Download PDFs'
            )
            ->assertSeeText(
                'Export data'
            )
            ->assertSee(
                route(
                    'consent-sessions.download-all',
                    [
                        'template_id' =>
                            $template->id,
                    ]
                ),
                false
            )
            ->assertSee(
                route(
                    'consent-sessions.export-data',
                    [
                        'template_id' =>
                            $template->id,
                    ]
                ),
                false
            );
    }

    private function createOrganizationUser(
        string $organizationName,
        string $organizationSlug
    ): array {
        $organization =
            Organization::query()->create([
                'name' =>
                    $organizationName,

                'slug' =>
                    $organizationSlug,
            ]);

        $user =
            User::factory()->create([
                'organization_id' =>
                    $organization->id,

                'is_active' =>
                    true,
            ]);

        $this->enablePaidOrganizationAccess(
            $organization,
            $user
        );

        return [
            $organization,
            $user,
        ];
    }

    private function createPublishedTemplate(
        Organization $organization,
        User $user,
        string $title,
        array $fields
    ): ConsentTemplate {
        $template =
            ConsentTemplate::query()->create([
                'organization_id' =>
                    $organization->id,

                'title' =>
                    $title,

                'description' =>
                    'Export data test consent.',

                'usage_type' =>
                    ConsentTemplate::USAGE_BOTH,

                'template_schema' => [
                    'builder_version' =>
                        2,

                    'consent_html' =>
                        '<p>I consent.</p>',

                    'consent_text' =>
                        'I consent.',

                    'additional_fields' =>
                        $fields,
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
                        $user->id,
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

    private function createConsentSession(
        Organization $organization,
        User $user,
        ConsentTemplate $template,
        string $signerName,
        ?string $signerEmail,
        array $responses
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

            'consent_campaign_id' =>
                null,

            'created_by' =>
                $user->id,

            'signer_name' =>
                $signerName,

            'signer_email' =>
                $signerEmail,

            'signer_reference' =>
                null,

            'access_token' =>
                (string) Str::uuid(),

            'status' =>
                ConsentSession::STATUS_COMPLETED,

            'responses' =>
                $responses,

            'started_at' =>
                now()->subMinute(),

            'completed_at' =>
                now(),
        ]);
    }
}
