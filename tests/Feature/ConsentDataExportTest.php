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

    public function test_csv_export_contains_only_selected_columns_and_formats_values(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser(
                'CSV Export Organization',
                'csv-export-organization'
            );

        $template =
            $this->createPublishedTemplate(
                organization: $organization,
                user: $user,
                title: 'CSV Export Consent',
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
                            'interests',

                        'type' =>
                            'checkboxes',

                        'label' =>
                            'Interests',

                        'required' =>
                            false,

                        'options' => [
                            'Email',
                            'Training',
                            'Events',
                        ],
                    ],
                    [
                        'id' =>
                            'confirmed',

                        'type' =>
                            'yes_no',

                        'label' =>
                            'Confirmed',

                        'required' =>
                            false,

                        'options' =>
                            [],
                    ],
                    [
                        'id' =>
                            'formula-text',

                        'type' =>
                            'text',

                        'label' =>
                            'Formula text',

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
            signerName: 'Jane Example',
            signerEmail: 'jane@example.com',
            responses: [
                'mobile-number' =>
                    '0712345678',

                'interests' => [
                    'Email',
                    'Training',
                ],

                'confirmed' =>
                    true,

                'formula-text' =>
                    '=1+1',

                'department' =>
                    'Operations',
            ]
        );

        $response =
            $this
                ->actingAs($user)
                ->post(
                    route(
                        'consent-sessions.export-data.download'
                    ),
                    [
                        'template_id' =>
                            $template->id,

                        'format' =>
                            'csv',

                        'columns' => [
                            'signer_name',
                            'signer_email',
                            'question:'
                                .$template->id
                                .':mobile-number',
                            'question:'
                                .$template->id
                                .':interests',
                            'question:'
                                .$template->id
                                .':confirmed',
                            'question:'
                                .$template->id
                                .':formula-text',
                        ],
                    ]
                );

        $response
            ->assertOk()
            ->assertDownload();

        $rows =
            $this->parseCsv(
                $response->streamedContent()
            );

        $this->assertCount(
            2,
            $rows
        );

        $this->assertSame(
            [
                'Name',
                'Email',
                'Mobile number',
                'Interests',
                'Confirmed',
                'Formula text',
            ],
            $rows[0]
        );

        $this->assertSame(
            [
                'Jane Example',
                'jane@example.com',
                '0712345678',
                'Email, Training',
                'Yes',
                "'=1+1",
            ],
            $rows[1]
        );

        $this->assertNotContains(
            'Department',
            $rows[0]
        );

        $this->assertNotContains(
            'Operations',
            $rows[1]
        );
    }

    public function test_xlsx_export_preserves_phone_and_formula_like_text_as_literal_strings(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser(
                'XLSX Export Organization',
                'xlsx-export-organization'
            );

        $template =
            $this->createPublishedTemplate(
                organization: $organization,
                user: $user,
                title: 'XLSX Export Consent',
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
                            'formula-text',

                        'type' =>
                            'text',

                        'label' =>
                            'Formula text',

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
            signerName: 'XLSX Signer',
            signerEmail: null,
            responses: [
                'mobile-number' =>
                    '0712345678',

                'formula-text' =>
                    '=1+1',

                'department' =>
                    'Operations',
            ]
        );

        $response =
            $this
                ->actingAs($user)
                ->post(
                    route(
                        'consent-sessions.export-data.download'
                    ),
                    [
                        'template_id' =>
                            $template->id,

                        'format' =>
                            'xlsx',

                        'columns' => [
                            'signer_name',
                            'question:'
                                .$template->id
                                .':mobile-number',
                            'question:'
                                .$template->id
                                .':formula-text',
                        ],
                    ]
                );

        $response
            ->assertOk()
            ->assertDownload();

        $xlsx =
            $response->streamedContent();

        $sharedStrings =
            $this->xlsxArchivePart(
                content:
                    $xlsx,

                part:
                    'xl/sharedStrings.xml'
            );

        $worksheet =
            $this->xlsxArchivePart(
                content:
                    $xlsx,

                part:
                    'xl/worksheets/sheet1.xml'
            );

        $this->assertStringContainsString(
            'Name',
            $sharedStrings
        );

        $this->assertStringContainsString(
            'Mobile number',
            $sharedStrings
        );

        $this->assertStringContainsString(
            'Formula text',
            $sharedStrings
        );

        $this->assertStringContainsString(
            'XLSX Signer',
            $sharedStrings
        );

        $this->assertStringContainsString(
            '0712345678',
            $sharedStrings
        );

        $this->assertStringContainsString(
            '=1+1',
            $sharedStrings
        );

        $this->assertStringNotContainsString(
            'Department',
            $sharedStrings
        );

        $this->assertStringNotContainsString(
            'Operations',
            $sharedStrings
        );

        $this->assertStringNotContainsString(
            '<f>',
            $worksheet
        );
    }

    public function test_export_rejects_columns_without_data_in_current_scope(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser(
                'Unavailable Column Organization',
                'unavailable-column-organization'
            );

        $template =
            $this->createPublishedTemplate(
                organization: $organization,
                user: $user,
                title: 'Unavailable Column Consent',
                fields: [
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
            signerName: 'Available Signer',
            signerEmail: null,
            responses: [
                'unused-field' =>
                    '',
            ]
        );

        $returnUrl =
            route(
                'consent-sessions.export-data',
                [
                    'template_id' =>
                        $template->id,
                ]
            );

        $this
            ->actingAs($user)
            ->from($returnUrl)
            ->post(
                route(
                    'consent-sessions.export-data.download'
                ),
                [
                    'template_id' =>
                        $template->id,

                    'format' =>
                        'csv',

                    'columns' => [
                        'question:'
                            .$template->id
                            .':unused-field',
                    ],
                ]
            )
            ->assertRedirect(
                $returnUrl
            )
            ->assertSessionHasErrors(
                'columns'
            );
    }

    public function test_export_uses_the_record_exact_published_version(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser(
                'Versioned Export Organization',
                'versioned-export-organization'
            );

        $template =
            $this->createPublishedTemplate(
                organization: $organization,
                user: $user,
                title: 'Versioned Consent',
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

        $this->createConsentSession(
            organization: $organization,
            user: $user,
            template: $template,
            signerName: 'Historical Signer',
            signerEmail: null,
            responses: [
                'department' =>
                    'Legacy Operations',
            ]
        );

        $newSchema =
            $template->template_schema;

        $newSchema[
            'additional_fields'
        ][0]['label'] =
            'Division';

        $versionTwo =
            $template
                ->versions()
                ->create([
                    'version_number' =>
                        2,

                    'title' =>
                        $template->title,

                    'description' =>
                        $template->description,

                    'template_schema' =>
                        $newSchema,

                    'published_at' =>
                        now(),

                    'published_by' =>
                        $user->id,
                ]);

        $template->update([
            'template_schema' =>
                $newSchema,

            'active_version_id' =>
                $versionTwo->id,

            'status' =>
                'published',

            'has_unpublished_changes' =>
                false,
        ]);

        $response =
            $this
                ->actingAs($user)
                ->post(
                    route(
                        'consent-sessions.export-data.download'
                    ),
                    [
                        'template_id' =>
                            $template->id,

                        'format' =>
                            'csv',

                        'columns' => [
                            'question:'
                                .$template->id
                                .':department',
                        ],
                    ]
                );

        $response
            ->assertOk()
            ->assertDownload();

        $rows =
            $this->parseCsv(
                $response->streamedContent()
            );

        $this->assertSame(
            [
                'Department',
            ],
            $rows[0]
        );

        $this->assertSame(
            [
                'Legacy Operations',
            ],
            $rows[1]
        );

        $this->assertNotContains(
            'Division',
            $rows[0]
        );
    }

    /**
     * Parse a streamed CSV response into rows.
     */
    private function parseCsv(
        string $content
    ): array {
        if (
            str_starts_with(
                $content,
                "\xEF\xBB\xBF"
            )
        ) {
            $content =
                substr(
                    $content,
                    3
                );
        }

        $handle =
            fopen(
                'php://temp',
                'w+b'
            );

        if ($handle === false) {
            $this->fail(
                'Could not open temporary CSV test stream.'
            );
        }

        fwrite(
            $handle,
            $content
        );

        rewind(
            $handle
        );

        $rows = [];

        try {
            while (
                (
                    $row =
                        fgetcsv(
                            $handle,
                            null,
                            ',',
                            '"',
                            ''
                        )
                ) !== false
            ) {
                $rows[] =
                    $row;
            }
        } finally {
            fclose(
                $handle
            );
        }

        return $rows;
    }

    /**
     * Read one XML file from an XLSX archive generated in memory.
     */
    private function xlsxArchivePart(
        string $content,
        string $part
    ): string {
        $temporaryPath =
            tempnam(
                sys_get_temp_dir(),
                'econsent-xlsx-test-'
            );

        if ($temporaryPath === false) {
            $this->fail(
                'Could not create temporary XLSX test file.'
            );
        }

        file_put_contents(
            $temporaryPath,
            $content
        );

        $archive =
            new \ZipArchive();

        try {
            $opened =
                $archive->open(
                    $temporaryPath
                );

            $this->assertTrue(
                $opened === true,
                'Generated XLSX archive could not be opened.'
            );

            $partContents =
                $archive->getFromName(
                    $part
                );

            $this->assertIsString(
                $partContents,
                "Generated XLSX archive is missing {$part}."
            );

            return $partContents;
        } finally {
            $archive->close();

            if (
                is_file(
                    $temporaryPath
                )
            ) {
                unlink(
                    $temporaryPath
                );
            }
        }
    }

    public function test_pdf_register_download_generates_a_real_pdf(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser(
                'PDF Register Organization',
                'pdf-register-organization'
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
                    '0712345678',

                'department' =>
                    'Operations',
            ]
        );

        $response =
            $this
                ->actingAs($user)
                ->post(
                    route(
                        'consent-sessions.export-data.download'
                    ),
                    [
                        'template_id' =>
                            $template->id,

                        'format' =>
                            'pdf',

                        'columns' => [
                            'signer_name',
                            'signer_email',
                            'question:'
                                .$template->id
                                .':mobile-number',
                            'question:'
                                .$template->id
                                .':department',
                        ],
                    ]
                );

        $response
            ->assertOk()
            ->assertDownload();

        $response->assertHeader(
            'content-type',
            'application/pdf'
        );

        $content =
            $response->getContent();

        $this->assertIsString(
            $content
        );

        $this->assertStringStartsWith(
            '%PDF-',
            $content
        );

        $this->assertGreaterThan(
            1000,
            strlen($content)
        );
    }

    public function test_pdf_register_rejects_more_than_twelve_selected_columns(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser(
                'PDF Limit Organization',
                'pdf-limit-organization'
            );

        $fields = [];

        for ($index = 1; $index <= 13; $index++) {
            $fields[] = [
                'id' =>
                    'field-'.$index,

                'type' =>
                    'text',

                'label' =>
                    'Field '.$index,

                'required' =>
                    false,

                'options' =>
                    [],
            ];
        }

        $template =
            $this->createPublishedTemplate(
                organization: $organization,
                user: $user,
                title: 'Wide Consent',
                fields: $fields
            );

        $responses = [];

        foreach ($fields as $field) {
            $responses[
                $field['id']
            ] =
                'Value '.$field['id'];
        }

        $this->createConsentSession(
            organization: $organization,
            user: $user,
            template: $template,
            signerName: 'Wide Export Signer',
            signerEmail: null,
            responses: $responses
        );

        $columns =
            collect($fields)
                ->map(
                    fn (array $field): string =>
                        'question:'
                        .$template->id
                        .':'
                        .$field['id']
                )
                ->all();

        $returnUrl =
            route(
                'consent-sessions.export-data',
                [
                    'template_id' =>
                        $template->id,
                ]
            );

        $this
            ->actingAs($user)
            ->from($returnUrl)
            ->post(
                route(
                    'consent-sessions.export-data.download'
                ),
                [
                    'template_id' =>
                        $template->id,

                    'format' =>
                        'pdf',

                    'columns' =>
                        $columns,
                ]
            )
            ->assertRedirect(
                $returnUrl
            )
            ->assertSessionHasErrors(
                'columns'
            );
    }

    public function test_pdf_register_view_renders_selected_register_information(): void
    {
        $html =
            view(
                'pdfs.consent-register',
                [
                    'title' =>
                        'Employee Consent',

                    'headings' =>
                        collect([
                            'Name',
                            'Mobile number',
                            'Department',
                        ]),

                    'rows' =>
                        collect([
                            [
                                'Jane Example',
                                '0712345678',
                                'Operations',
                            ],
                        ]),

                    'recordCount' =>
                        1,

                    'generatedAt' =>
                        now(),

                    'tableFontSize' =>
                        9,
                ]
            )
                ->render();

        $this->assertStringContainsString(
            'Employee Consent',
            $html
        );

        $this->assertStringContainsString(
            'Consent Register',
            $html
        );

        $this->assertStringContainsString(
            'Mobile number',
            $html
        );

        $this->assertStringContainsString(
            'Jane Example',
            $html
        );

        $this->assertStringContainsString(
            '0712345678',
            $html
        );

        $this->assertStringContainsString(
            'Operations',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/<strong>\s*1\s*<\/strong>\s*record/',
            $html
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
