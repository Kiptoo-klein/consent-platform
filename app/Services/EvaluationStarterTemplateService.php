<?php

namespace App\Services;

use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\SigningStation;

final class EvaluationStarterTemplateService
{
    public const GENERAL_CONSENT =
        'general_consent';

    public const PHOTO_MEDIA_CONSENT =
        'photo_media_consent';

    public const DATA_INFORMATION_CONSENT =
        'data_information_consent';

    public function __construct(
        private readonly ConsentTemplateContentService
            $contentService
    ) {
    }

    /**
     * Create the three starter templates for a newly registered
     * Evaluation organization.
     *
     * This is deliberately idempotent: rerunning provisioning must
     * never overwrite an organization's edits or restore a starter
     * that was retired after payment.
     */
    public function provision(
        Organization $organization
    ): void {
        foreach (
            $this->definitions(
                $organization
            )
            as $definition
        ) {
            $existing =
                ConsentTemplate::query()
                    ->where(
                        'organization_id',
                        $organization->id
                    )
                    ->where(
                        'evaluation_starter_key',
                        $definition['key']
                    )
                    ->first();

            if ($existing !== null) {
                continue;
            }

            $prepared =
                $this->contentService->prepare(
                    $definition['content'],
                    (int) $organization->id
                );

            ConsentTemplate::query()->create([
                'organization_id' =>
                    $organization->id,

                'title' =>
                    $definition['title'],

                'description' =>
                    $definition['description'],

                'category' =>
                    'Evaluation starter',

                /*
                 * The starters can be tested through either the
                 * individual-consent or signing-station workflow.
                 */
                'usage_type' =>
                    ConsentTemplate::USAGE_BOTH,

                'template_schema' => [
                    'builder_version' => 2,

                    'consent_html' =>
                        $prepared['html'],

                    'consent_text' =>
                        $prepared['text'],

                    'additional_fields' =>
                        [],
                ],

                'active_version_id' =>
                    null,

                'has_unpublished_changes' =>
                    true,

                'status' =>
                    'draft',

                'evaluation_starter_key' =>
                    $definition['key'],

                'evaluation_retired_at' =>
                    null,
            ]);
        }
    }

    /**
     * Permanently retire all Evaluation starters after successful
     * paid-plan activation.
     *
     * Rows and published versions remain in the database so existing
     * consent records continue to have their historical evidence.
     */
    public function retireForOrganization(
        int $organizationId
    ): void {
        $starters =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->whereNotNull(
                    'evaluation_starter_key'
                )
                ->whereNull(
                    'evaluation_retired_at'
                )
                ->lockForUpdate()
                ->get();

        if ($starters->isEmpty()) {
            return;
        }

        $starterIds =
            $starters
                ->pluck('id')
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->all();

        /*
         * A paid organization must not be left with a live kiosk that
         * still points at a starter which is about to disappear.
         */
        SigningStation::query()
            ->where(
                'organization_id',
                $organizationId
            )
            ->whereIn(
                'consent_template_id',
                $starterIds
            )
            ->where(
                'active',
                true
            )
            ->update([
                'active' => false,
            ]);

        foreach ($starters as $starter) {
            $starter->forceFill([
                /*
                 * Taking the active version offline prevents any new
                 * consent from being started from this template while
                 * preserving every published version row.
                 */
                'active_version_id' =>
                    null,

                'status' =>
                    'archived',

                'evaluation_retired_at' =>
                    now(),
            ])->save();
        }
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     title: string,
     *     description: string,
     *     content: string
     * }>
     */
    private function definitions(
        Organization $organization
    ): array {
        $organizationName =
            htmlspecialchars(
                (string) $organization->name,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

        return [
            [
                'key' =>
                    self::GENERAL_CONSENT,

                'title' =>
                    'General Consent',

                'description' =>
                    'Editable starter for a general consent workflow.',

                'content' =>
                    <<<HTML
<h2>General Consent</h2>
<p>I confirm that I have received information from {$organizationName} about the activity, service, or process described in this consent.</p>
<p>I have had the opportunity to ask questions and understand the choices available to me. I understand that my consent is voluntary.</p>
<p>I may contact {$organizationName} if I have questions or wish to withdraw consent for future activities, subject to actions already completed or information that must lawfully be retained.</p>
<p><strong>Before using this template:</strong> edit this sample so it clearly describes the exact activity, purpose, important information, choices, and contact details that apply to your organization.</p>
HTML,
            ],

            [
                'key' =>
                    self::PHOTO_MEDIA_CONSENT,

                'title' =>
                    'Photo & Media Consent',

                'description' =>
                    'Editable starter for photo, video and media consent.',

                'content' =>
                    <<<HTML
<h2>Photo &amp; Media Consent</h2>
<p>I consent to {$organizationName} capturing photographs, video, audio, or other media involving me for the purposes described in this consent.</p>
<p>I understand that the approved media may be edited, reproduced, published, or distributed through the channels specifically described by the organization.</p>
<p>I may contact {$organizationName} to withdraw permission for future use. I understand that withdrawal may not remove material that was already lawfully published or distributed before the withdrawal was received.</p>
<p><strong>Before using this template:</strong> specify exactly what media will be collected, where it may appear, the intended purpose, how long permission applies, and how a person can contact your organization.</p>
HTML,
            ],

            [
                'key' =>
                    self::DATA_INFORMATION_CONSENT,

                'title' =>
                    'Data / Information Consent',

                'description' =>
                    'Editable starter for collection and use of information.',

                'content' =>
                    <<<HTML
<h2>Data / Information Consent</h2>
<p>I consent to {$organizationName} collecting and using the information described in this consent for the stated purpose.</p>
<p>I understand what information is being requested, why it is needed, and how it will be used. I understand that access should be limited to people who require the information for the stated purpose.</p>
<p>I may contact {$organizationName} with questions about my information or to request withdrawal of consent for future use, subject to applicable retention or legal requirements.</p>
<p><strong>Before using this template:</strong> identify the exact information collected, purpose of use, who may access it, retention period, any sharing involved, and the appropriate privacy contact.</p>
HTML,
            ],
        ];
    }
}
