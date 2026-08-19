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
        }
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
                            'SMS',
                        ],
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

        $this->assertSame(
            [
                'contact-phone' =>
                    '+254 712 345 678',

                'interests' => [
                    'Email',
                    'SMS',
                ],
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
            ],
            $consentSession->responses
        );

        $this->assertSame(
            $template->active_version_id,
            $consentSession
                ->consent_template_version_id
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
                                'SMS',
                                'Training',
                            ],
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
