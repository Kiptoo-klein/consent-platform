<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsentPdfSelectedFieldsVisibilityTest extends TestCase
{
    public function test_pdf_does_not_render_legacy_reference_number_separately(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/pdfs/consent-record.blade.php'
            )
        );

        $this->assertIsString($view);

        $this->assertStringNotContainsString(
            '$consentSession->signer_reference',
            $view
        );

        $this->assertStringNotContainsString(
            '<div class="field-label">Reference number</div>',
            $view
        );
    }

    public function test_pdf_still_renders_selected_custom_field_evidence(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/pdfs/consent-record.blade.php'
            )
        );

        $this->assertIsString($view);

        $this->assertStringContainsString(
            '@if (count($responseEvidence) > 0)',
            $view
        );

        $this->assertStringContainsString(
            '@foreach ($responseEvidence as $evidence)',
            $view
        );

        $this->assertStringContainsString(
            '{{ $evidence[\'label\'] }}',
            $view
        );

        $this->assertStringContainsString(
            '{{ $formatValue(',
            $view
        );

        $this->assertStringContainsString(
            '$evidence[\'type\'] ?? null',
            $view
        );
    }
}
