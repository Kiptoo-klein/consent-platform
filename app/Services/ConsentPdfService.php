<?php

namespace App\Services;

use App\Models\ConsentSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ConsentPdfService
{
    public function __construct(
        private readonly ConsentAuditService $consentAuditService
    ) {
    }


    /**
     * Storage disk containing immutable consent PDFs.
     */
    public function diskName(): string
    {
        return (string) config(
            'consent-pdf.disk',
            'local'
        );
    }

/**
     * Generate and permanently store the consent PDF.
     */
    public function generateAndStore(
        ConsentSession $consentSession
    ): string {
        $this->ensureRecordCanBeGenerated(
            $consentSession
        );

        $consentSession->loadMissing([
            'organization',
            'consentTemplate',
            'consentTemplateVersion.publisher',
            'creator',
            'signingStation',
            'signature',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Do not silently create a second legal record
        |--------------------------------------------------------------------------
        |
        | If a PDF has already been generated and the stored file still exists,
        | return the existing path instead of generating a different document.
        |
        */

        if (
            filled($consentSession->pdf_path)
            && Storage::disk($this->diskName())->exists(
                $consentSession->pdf_path
            )
        ) {
            /*
             * Ensure older generated records receive the audit event
             * without generating another PDF or duplicating the event.
             */
            $this->recordPdfGeneratedAuditIfMissing(
                $consentSession
            );

            return $consentSession->pdf_path;
        }

        $publishedVersion =
            $consentSession->consentTemplateVersion;

        $additionalFields = $this->additionalFields(
            $consentSession
        );

        $responseEvidence = $this->responseEvidence(
            $consentSession,
            $additionalFields
        );

        $consentText =
            data_get(
                $publishedVersion,
                'consent_text'
            )
            ?? data_get(
                $publishedVersion,
                'template_schema.consent_text'
            )
            ?? data_get(
                $publishedVersion,
                'template_schema.content'
            )
            ?? data_get(
                $publishedVersion,
                'content'
            );

        $pdf = Pdf::loadView(
            'pdfs.consent-record',
            [
                'consentSession' =>
                    $consentSession,

                'publishedVersion' =>
                    $publishedVersion,

                'additionalFields' =>
                    $additionalFields,

                'responseEvidence' =>
                    $responseEvidence,

                'consentText' =>
                    $consentText,
            ]
        );

        $pdf->setPaper(
            'a4',
            'portrait'
        );

        $path = $this->storagePath();

        $stored = Storage::disk($this->diskName())->put(
            $path,
            $pdf->output()
        );

        if (! $stored) {
            throw new RuntimeException(
                'The consent PDF could not be saved.'
            );
        }

        $consentSession->forceFill([
            'pdf_path' =>
                $path,

            'pdf_generated_at' =>
                now(),
        ])->save();

        /*
         * Record the permanent audit event only after the file
         * and database information have been saved successfully.
         */
        $this->recordPdfGeneratedAuditIfMissing(
            $consentSession
        );

        return $path;
    }

    /**
     * Return the friendly filename used for downloads
     * and email attachments.
     */
    public function downloadFilename(
        ConsentSession $consentSession
    ): string {
        $consentSession->loadMissing([
            'organization',
            'consentTemplate',
            'consentTemplateVersion',
        ]);

        $organizationName = Str::slug(
            $consentSession->organization?->name
                ?: 'organization',
            '_'
        );

        $signerName = Str::slug(
            $consentSession->signer_name
                ?: 'signer',
            '_'
        );

        $templateTitle = Str::slug(
            $consentSession->consentTemplateVersion?->title
                ?? $consentSession->consentTemplate?->title
                ?? 'consent',
            '_'
        );

        $date = optional(
            $consentSession->completed_at
        )->format('Y-m-d')
            ?? now()->format('Y-m-d');

        return Str::limit(
            "{$organizationName}_{$signerName}_{$templateTitle}_{$date}",
            180,
            ''
        ).'.pdf';
    }

    /**
     * Record the PDF generation event once.
     */
    private function recordPdfGeneratedAuditIfMissing(
        ConsentSession $consentSession
    ): void {
        $eventAlreadyExists = $consentSession
            ->auditEvents()
            ->where(
                'event_type',
                'consent.pdf_generated'
            )
            ->exists();

        if ($eventAlreadyExists) {
            return;
        }

        $this->consentAuditService->record(
            consentSession: $consentSession,
            eventType: 'consent.pdf_generated',
            description:
                'The completed consent PDF was generated and stored.',
            metadata: [
                'storage_disk' =>
                    'local',

                'pdf_path' =>
                    $consentSession->pdf_path,

                'pdf_generated_at' =>
                    (string) $consentSession->pdf_generated_at,
            ]
        );
    }

    /**
     * Completed records with a valid signature and version
     * are eligible for permanent PDF generation.
     */
    private function ensureRecordCanBeGenerated(
        ConsentSession $consentSession
    ): void {
        abort_unless(
            $consentSession->isCompleted(),
            422,
            'Only completed consent records can generate a PDF.'
        );

        $consentSession->loadMissing([
            'signature',
            'consentTemplateVersion',
        ]);

        abort_unless(
            $consentSession->signature
                && $consentSession->signature->isSigned(),
            422,
            'This completed consent record does not contain a valid signature.'
        );

        abort_unless(
            $consentSession->consentTemplateVersion !== null,
            422,
            'This consent record does not have a published template version.'
        );
    }

    /**
     * Generate a secure private storage path.
     */
    private function storagePath(): string
    {
        return sprintf(
            'consent-records/%s/%s.pdf',
            now()->format('Y/m/d'),
            (string) Str::uuid()
        );
    }

    /**
     * Read dynamic fields from the exact published
     * version linked to the consent record.
     */
    private function additionalFields(
        ConsentSession $consentSession
    ): array {
        $fields = data_get(
            $consentSession->consentTemplateVersion,
            'template_schema.additional_fields',
            []
        );

        if (! is_array($fields)) {
            return [];
        }

        return array_values(
            array_filter(
                $fields,
                fn ($field): bool =>
                    is_array($field)
            )
        );
    }

    /**
     * Match stored responses with labels and types
     * from the published template version.
     */
    private function responseEvidence(
        ConsentSession $consentSession,
        array $additionalFields
    ): array {
        $responses =
            $consentSession->responses ?? [];

        if (! is_array($responses)) {
            return [];
        }

        $fieldDefinitions = [];

        foreach (
            $additionalFields as $index => $field
        ) {
            $key = $this->fieldKey(
                $field,
                $index
            );

            $fieldDefinitions[$key] = [
                'label' =>
                    $field['label']
                    ?? $this->humanizeKey($key),

                'type' =>
                    $field['type']
                    ?? 'text',
            ];
        }

        $evidence = [];

        foreach (
            $responses as $key => $value
        ) {
            $stringKey =
                (string) $key;

            $definition =
                $fieldDefinitions[$stringKey]
                ?? null;

            $evidence[] = [
                'key' =>
                    $stringKey,

                'label' =>
                    $definition['label']
                    ?? $this->humanizeKey(
                        $stringKey
                    ),

                'type' =>
                    $definition['type']
                    ?? 'text',

                'value' =>
                    $value,
            ];
        }

        return $evidence;
    }

    /**
     * Determine the storage key for a dynamic field.
     */
    private function fieldKey(
        array $field,
        int $index
    ): string {
        return (string) (
            $field['name']
            ?? $field['key']
            ?? $field['id']
            ?? 'field_'.$index
        );
    }

    /**
     * Convert a field key into a readable label.
     */
    private function humanizeKey(
        string $key
    ): string {
        return Str::of($key)
            ->replace([
                '_',
                '-',
            ], ' ')
            ->squish()
            ->title()
            ->toString();
    }
}
