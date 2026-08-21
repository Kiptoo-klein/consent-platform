<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsentRecordInternalIdVisibilityTest extends TestCase
{
    public function test_consent_record_list_does_not_expose_database_record_number(): void
    {
        $view = file_get_contents(
            resource_path('views/consent-sessions/index.blade.php')
        );

        $this->assertIsString($view);

        $this->assertStringNotContainsString(
            'Record #{{ $consentSession->id }}',
            $view
        );
    }

    public function test_consent_evidence_page_does_not_present_database_id_as_record_or_evidence_number(): void
    {
        $view = file_get_contents(
            resource_path('views/consent-sessions/show.blade.php')
        );

        $this->assertIsString($view);

        $this->assertStringNotContainsString(
            'Record #{{ $consentSession->id }}',
            $view
        );

        $this->assertStringNotContainsString(
            '$recordIdentifier',
            $view
        );

        $this->assertStringNotContainsString(
            'Database Record',
            $view
        );

        $this->assertStringContainsString(
            'cancel-consent-record-{{ $consentSession->id }}',
            $view
        );
    }

    public function test_consent_audit_heading_does_not_expose_database_record_number(): void
    {
        $view = file_get_contents(
            resource_path('views/consent-audit/show.blade.php')
        );

        $this->assertIsString($view);

        $this->assertStringNotContainsString(
            '#{{ $consentSession->id }}',
            $view
        );

        $this->assertStringContainsString(
            'Permanent history for this consent record',
            $view
        );
    }

    public function test_consent_pdf_does_not_expose_consent_session_database_id(): void
    {
        $view = file_get_contents(
            resource_path('views/pdfs/consent-record.blade.php')
        );

        $this->assertIsString($view);

        $this->assertStringNotContainsString(
            '$consentSession->id',
            $view
        );

        $this->assertStringNotContainsString(
            'Record identifier',
            $view
        );

        $this->assertStringContainsString(
            '<title>'."\n".'        Consent Record'."\n".'    </title>',
            $view
        );
    }

    public function test_signed_consent_email_only_displays_real_signer_reference(): void
    {
        $view = file_get_contents(
            resource_path('views/emails/signed-consent-copy.blade.php')
        );

        $this->assertIsString($view);

        $this->assertStringNotContainsString(
            "'Consent record #'.\$consentSession->id",
            $view
        );

        $this->assertStringContainsString(
            '$consentSession->signer_reference;',
            $view
        );

        $this->assertStringContainsString(
            '@if ($recordReference)',
            $view
        );
    }
}
