<?php

namespace Tests\Feature;

use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\User;
use App\Services\SigningStationDeviceLeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConsentTemplateStructuredQuestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_preserves_typed_consent_questions(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser();

        $questions = [
            [
                'id' => 'contact-phone',
                'type' => 'phone',
                'label' => 'Contact Phone',
                'required' => true,
            ],
            [
                'id' => 'interests',
                'type' => 'checkboxes',
                'label' => 'Interests',
                'required' => false,
                'options' => [
                    'Training',
                    'Events',
                ],
            ],
        ];

        $this
            ->actingAs($user)
            ->post(
                route('consent-templates.store'),
                [
                    'title' =>
                        'Structured Consent',

                    'description' =>
                        'Consent with typed questions.',

                    'content' =>
                        '<p>I consent.</p>',

                    'additional_fields_json' =>
                        json_encode(
                            $questions,
                            JSON_THROW_ON_ERROR
                        ),
                ]
            )
            ->assertRedirect(
                route('consent-templates.manage')
            );

        $template = ConsentTemplate::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->where(
                'title',
                'Structured Consent'
            )
            ->firstOrFail();

        $this->assertSame(
            [
                [
                    'id' => 'contact-phone',
                    'type' => 'phone',
                    'label' => 'Contact Phone',
                    'required' => true,
                    'options' => [],
                ],
                [
                    'id' => 'interests',
                    'type' => 'checkboxes',
                    'label' => 'Interests',
                    'required' => false,
                    'options' => [
                        'Training',
                        'Events',
                    ],
                ],
            ],
            $template->template_schema[
                'additional_fields'
            ]
        );
    }

    public function test_update_preserves_types_and_defaults_legacy_fields_to_text(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser();

        $template = ConsentTemplate::create([
            'organization_id' =>
                $organization->id,

            'title' =>
                'Editable Consent',

            'description' =>
                'Before update.',

            'usage_type' =>
                ConsentTemplate::USAGE_BOTH,

            'template_schema' => [
                'builder_version' => 2,
                'consent_html' =>
                    '<p>Before update.</p>',
                'consent_text' =>
                    'Before update.',
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

        $questions = [
            [
                'id' => 'department',
                'label' => 'Department',
                'required' => false,
            ],
            [
                'id' => 'decision',
                'type' => 'radio',
                'label' => 'Decision',
                'required' => true,
                'options' => [
                    'Approve',
                    'Decline',
                ],
            ],
        ];

        $this
            ->actingAs($user)
            ->put(
                route(
                    'consent-templates.update',
                    $template
                ),
                [
                    'title' =>
                        'Editable Consent',

                    'description' =>
                        'After update.',

                    'content' =>
                        '<p>After update.</p>',

                    'additional_fields_json' =>
                        json_encode(
                            $questions,
                            JSON_THROW_ON_ERROR
                        ),
                ]
            )
            ->assertRedirect(
                route('consent-templates.manage')
            );

        $template->refresh();

        $this->assertSame(
            [
                [
                    'id' => 'department',
                    'type' => 'text',
                    'label' => 'Department',
                    'required' => false,
                    'options' => [],
                ],
                [
                    'id' => 'decision',
                    'type' => 'radio',
                    'label' => 'Decision',
                    'required' => true,
                    'options' => [
                        'Approve',
                        'Decline',
                    ],
                ],
            ],
            $template->template_schema[
                'additional_fields'
            ]
        );
    }

    public function test_create_preserves_yes_no_question_type(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser();

        $this
            ->actingAs($user)
            ->post(
                route(
                    'consent-templates.store'
                ),
                [
                    'title' =>
                        'Yes No Consent',

                    'description' =>
                        'Consent with an explicit Yes or No question.',

                    'content' =>
                        '<p>Please review.</p>',

                    'additional_fields_json' =>
                        json_encode(
                            [
                                [
                                    'id' =>
                                        'approved',

                                    'type' =>
                                        'yes_no',

                                    'label' =>
                                        'Do you approve?',

                                    'required' =>
                                        true,
                                ],
                            ],
                            JSON_THROW_ON_ERROR
                        ),
                ]
            )
            ->assertRedirect(
                route(
                    'consent-templates.manage'
                )
            );

        $template =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->where(
                    'title',
                    'Yes No Consent'
                )
                ->firstOrFail();

        $this->assertSame(
            [
                [
                    'id' =>
                        'approved',

                    'type' =>
                        'yes_no',

                    'label' =>
                        'Do you approve?',

                    'required' =>
                        true,

                    'options' =>
                        [],
                ],
            ],
            $template->template_schema[
                'additional_fields'
            ]
        );
    }

    public function test_create_and_edit_use_the_structured_question_builder(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser();

        $template =
            ConsentTemplate::query()->create([
                'organization_id' =>
                    $organization->id,

                'title' =>
                    'Builder Test Consent',

                'description' =>
                    'Builder test.',

                'usage_type' =>
                    ConsentTemplate::USAGE_BOTH,

                'template_schema' => [
                    'builder_version' =>
                        2,

                    'consent_html' =>
                        '<p>Builder test.</p>',

                    'consent_text' =>
                        'Builder test.',

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

        foreach ([
            route(
                'consent-templates.create'
            ),

            route(
                'consent-templates.edit',
                $template
            ),
        ] as $url) {
            $response =
                $this
                    ->actingAs($user)
                    ->get($url);

            $response
                ->assertOk()
                ->assertSeeText(
                    'Information to collect'
                )
                ->assertSeeText(
                    '+ Add question'
                )
                ->assertSeeText(
                    'Short answer'
                )
                ->assertSeeText(
                    'Paragraph'
                )
                ->assertSeeText(
                    'Email'
                )
                ->assertSeeText(
                    'Phone number'
                )
                ->assertSeeText(
                    'Number'
                )
                ->assertSeeText(
                    'Date'
                )
                ->assertSeeText(
                    'Yes / No'
                )
                ->assertSee(
                    'value="yes_no"',
                    false
                )
                ->assertSeeText(
                    'Multiple choice'
                )
                ->assertSeeText(
                    'Checkboxes'
                )
                ->assertSeeText(
                    'Dropdown'
                )
                ->assertSeeText(
                    'Move up'
                )
                ->assertSeeText(
                    'Move down'
                )
                ->assertSeeText(
                    'Required'
                )
                ->assertSeeText(
                    'Signing information'
                )
                ->assertSeeText(
                    'Signer name'
                )
                ->assertSeeText(
                    'Signature'
                )
                ->assertDontSeeText(
                    'Additional Fields'
                )
                ->assertDontSeeText(
                    '+ Add Additional Field'
                )
                ->assertDontSeeText(
                    'Standard Signer Information'
                )
                ->assertDontSeeText(
                    'Automatically Added'
                )
                ->assertDontSeeText(
                    'Secure Timestamp'
                )
                ->assertDontSeeText(
                    'Document ID'
                )
                ->assertDontSeeText(
                    'Consent Version'
                );
        }
    }

    public function test_public_question_renderers_support_phone_and_checkbox_groups(): void
    {
        foreach ([
            'public-consent/show.blade.php',
            'public-signing-stations/partials/additional-fields-inline.blade.php',
        ] as $relativeView) {
            $view = file_get_contents(
                resource_path(
                    'views/'.$relativeView
                )
            );

            $this->assertIsString($view);

            $this->assertStringContainsString(
                "\$fieldType === 'phone'",
                $view
            );

            $this->assertStringContainsString(
                "'tel'",
                $view
            );

            $this->assertStringContainsString(
                "\$fieldType === 'checkboxes'",
                $view
            );

            $this->assertStringContainsString(
                'name="responses[{{ $fieldKey }}][]"',
                $view
            );

            $this->assertStringContainsString(
                "\$fieldType === 'yes_no'",
                $view
            );

            $this->assertStringContainsString(
                "\$fieldType === 'checkbox'",
                $view
            );
        }
    }

    public function test_yes_no_record_and_pdf_display_are_type_aware(): void
    {
        $recordView =
            file_get_contents(
                resource_path(
                    'views/consent-sessions/show.blade.php'
                )
            );

        $pdfView =
            file_get_contents(
                resource_path(
                    'views/pdfs/consent-record.blade.php'
                )
            );

        $this->assertIsString(
            $recordView
        );

        $this->assertIsString(
            $pdfView
        );

        $this->assertStringContainsString(
            "'yes_no'",
            $recordView
        );

        $this->assertStringContainsString(
            "'yes_no'",
            $pdfView
        );

        $this->assertStringContainsString(
            "\$evidence['type'] ?? null",
            $pdfView
        );
    }

    public function test_one_person_submission_preserves_phone_and_checkbox_group_answers(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser();

        $template =
            $this->createPublishedStructuredTemplate(
                $organization,
                $user
            );

        $consentSession =
            $this->createManualConsentSession(
                $organization,
                $user,
                $template
            );

        $this
            ->patch(
                route(
                    'public-consent.update',
                    $consentSession->access_token
                ),
                [
                    'responses' => [
                        'contact-phone' =>
                            '+254 712 345 678',

                        'interests' => [
                            'Email',
                            'Events',
                        ],

                        'confirmed' =>
                            '1',
                    ],
                ]
            )
            ->assertRedirect(
                route(
                    'public-consent.signature',
                    $consentSession->access_token
                )
            );

        $consentSession->refresh();

        $this->assertSame(
            ConsentSession::STATUS_IN_PROGRESS,
            $consentSession->status
        );

        $this->assertEquals(
            [
                'contact-phone' =>
                    '+254 712 345 678',

                'interests' => [
                    'Email',
                    'Events',
                ],

                'confirmed' =>
                    '1',
            ],
            $consentSession->responses
        );
    }

    public function test_one_person_submission_rejects_unknown_checkbox_group_option(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser();

        $template =
            $this->createPublishedStructuredTemplate(
                $organization,
                $user
            );

        $consentSession =
            $this->createManualConsentSession(
                $organization,
                $user,
                $template
            );

        $originalResponses = [
            'contact-phone' =>
                '+254 700 111 222',

            'interests' => [
                'Email',
            ],

            'confirmed' =>
                '0',
        ];

        $consentSession->update([
            'responses' =>
                $originalResponses,
        ]);

        $this
            ->from(
                route(
                    'public-consent.show',
                    $consentSession->access_token
                )
            )
            ->patch(
                route(
                    'public-consent.update',
                    $consentSession->access_token
                ),
                [
                    'responses' => [
                        'contact-phone' =>
                            '+254 700 111 222',

                        'interests' => [
                            'Not an option',
                        ],

                        'confirmed' =>
                            '0',
                    ],
                ]
            )
            ->assertRedirect(
                route(
                    'public-consent.show',
                    $consentSession->access_token
                )
            )
            ->assertSessionHasErrors([
                'responses.interests.0',
            ]);

        $consentSession->refresh();

        $this->assertSame(
            ConsentSession::STATUS_PENDING,
            $consentSession->status
        );

        $this->assertSame(
            $originalResponses,
            $consentSession->responses
        );
    }

    public function test_signing_station_submission_preserves_phone_and_checkbox_group_answers(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser();

        $template =
            $this->createPublishedStructuredTemplate(
                $organization,
                $user
            );

        $station =
            $this->createSigningStation(
                $organization,
                $user,
                $template
            );

        $response =
            $this
                ->withSession([
                    SigningStationDeviceLeaseService::
                        SESSION_KEY =>
                            str_repeat('S', 64),

                    'signing_station_reviewed_'.
                        $station->id =>
                            true,
                ])
                ->post(
                    route(
                        'public-signing-stations.start',
                        $station->station_token
                    ),
                    [
                        'signer_name' =>
                            'Structured Station Signer',

                        'signer_email' =>
                            '',

                        'responses' => [
                            'contact-phone' =>
                                '+254 700 000 000',

                            'interests' => [
                                'Training',
                            ],

                            'confirmed' =>
                                '0',
                        ],
                    ]
                );

        $consentSession =
            ConsentSession::query()
                ->where(
                    'signing_station_id',
                    $station->id
                )
                ->where(
                    'signer_name',
                    'Structured Station Signer'
                )
                ->sole();

        $response->assertRedirect(
            route(
                'public-consent.signature',
                $consentSession->access_token
            )
        );

        $this->assertSame(
            ConsentSession::STATUS_IN_PROGRESS,
            $consentSession->status
        );

        $this->assertSame(
            [
                'contact-phone' =>
                    '+254 700 000 000',

                'interests' => [
                    'Training',
                ],

                'confirmed' =>
                    '0',
            ],
            $consentSession->responses
        );

        $this->assertSame(
            $template->active_version_id,
            $consentSession
                ->consent_template_version_id
        );
    }


    public function test_record_displays_checkbox_groups_and_legacy_text_fields(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser();

        $template =
            ConsentTemplate::query()->create([
                'organization_id' =>
                    $organization->id,

                'title' =>
                    'Compatibility Consent',

                'description' =>
                    'Structured compatibility test.',

                'usage_type' =>
                    ConsentTemplate::USAGE_BOTH,

                'template_schema' => [
                    'builder_version' =>
                        2,

                    'consent_html' =>
                        '<p>Compatibility consent.</p>',

                    'consent_text' =>
                        'Compatibility consent.',

                    'additional_fields' => [
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
                            ],
                        ],
                        [
                            'id' =>
                                'department',

                            /*
                             * Deliberately no type:
                             * legacy fields default to text.
                             */
                            'label' =>
                                'Department',

                            'required' =>
                                false,
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

        $session =
            $this->createManualConsentSession(
                $organization,
                $user,
                $template->refresh()
            );

        $session->update([
            'responses' => [
                'interests' => [
                    'Email',
                    'Training',
                ],

                'department' =>
                    'Operations',
            ],
        ]);

        $this
            ->actingAs($user)
            ->get(
                route(
                    'consent-sessions.show',
                    $session
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Interests'
            )
            ->assertSeeText(
                'Email, Training'
            )
            ->assertSeeText(
                'Department'
            )
            ->assertSeeText(
                'Operations'
            );
    }

    public function test_record_keeps_fields_from_its_original_published_version(): void
    {
        [$organization, $user] =
            $this->createOrganizationUser();

        $template =
            $this->createPublishedStructuredTemplate(
                $organization,
                $user
            );

        $versionOneId =
            $template->active_version_id;

        $session =
            $this->createManualConsentSession(
                $organization,
                $user,
                $template
            );

        $session->update([
            'responses' => [
                'contact-phone' =>
                    '+254 711 222 333',
            ],
        ]);

        $versionTwo =
            $template
                ->versions()
                ->create([
                    'version_number' =>
                        2,

                    'title' =>
                        $template->title,

                    'description' =>
                        'Updated version.',

                    'template_schema' => [
                        'builder_version' =>
                            2,

                        'consent_html' =>
                            '<p>Updated consent.</p>',

                        'consent_text' =>
                            'Updated consent.',

                        'additional_fields' => [
                            [
                                'id' =>
                                    'contact-phone',

                                'type' =>
                                    'phone',

                                'label' =>
                                    'Renamed Contact Number',

                                'required' =>
                                    true,

                                'options' =>
                                    [],
                            ],
                        ],
                    ],

                    'published_at' =>
                        now(),

                    'published_by' =>
                        $user->id,
                ]);

        $template->update([
            'active_version_id' =>
                $versionTwo->id,

            'has_unpublished_changes' =>
                false,

            'status' =>
                'published',
        ]);

        $this->assertNotSame(
            $versionOneId,
            $template->fresh()->active_version_id
        );

        $this->assertSame(
            $versionOneId,
            $session
                ->fresh()
                ->consent_template_version_id
        );

        $this
            ->actingAs($user)
            ->get(
                route(
                    'consent-sessions.show',
                    $session
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Contact Phone'
            )
            ->assertSeeText(
                '+254 711 222 333'
            )
            ->assertDontSeeText(
                'Renamed Contact Number'
            );
    }

    private function createPublishedStructuredTemplate(
        Organization $organization,
        User $user
    ): ConsentTemplate {
        $template =
            ConsentTemplate::query()->create([
                'organization_id' =>
                    $organization->id,

                'title' =>
                    'Runtime Structured Consent',

                'description' =>
                    'Runtime structured question test.',

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
                                'contact-phone',

                            'type' =>
                                'phone',

                            'label' =>
                                'Contact Phone',

                            'required' =>
                                true,

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
                                true,

                            'options' => [
                                'Email',
                                'Events',
                                'Training',
                            ],
                        ],
                        [
                            'id' =>
                                'confirmed',

                            'type' =>
                                'yes_no',

                            'label' =>
                                'Do you confirm?',

                            'required' =>
                                true,

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

    private function createManualConsentSession(
        Organization $organization,
        User $user,
        ConsentTemplate $template
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
                'Structured Question Signer',

            'signer_email' =>
                null,

            'signer_reference' =>
                null,

            'access_token' =>
                (string) Str::uuid(),

            'status' =>
                ConsentSession::STATUS_PENDING,

            'responses' =>
                [],
        ]);
    }

    private function createSigningStation(
        Organization $organization,
        User $user,
        ConsentTemplate $template
    ): SigningStation {
        return SigningStation::query()->create([
            'organization_id' =>
                $organization->id,

            'consent_template_id' =>
                $template->id,

            'created_by' =>
                $user->id,

            'name' =>
                'Structured Questions Station',

            'station_token' =>
                Str::random(64),

            'active' =>
                true,

            'require_email' =>
                false,

            'require_reference' =>
                false,

            'auto_reset_seconds' =>
                3,

            'qr_expires_at' =>
                now()->addHours(24),
        ]);
    }

    /**
     * @return array{Organization, User}
     */
    private function createOrganizationUser(): array
    {
        $organization = Organization::create([
            'name' =>
                'Structured Questions Organization',

            'slug' =>
                'structured-questions-organization',
        ]);

        $user = User::factory()->create([
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
}
