<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsentPdfCloudStorageWiringTest extends TestCase
{
    public function test_pdf_generation_uses_configured_disk(): void
    {
        $service = file_get_contents(
            app_path(
                'Services/ConsentPdfService.php'
            )
        );

        $this->assertIsString($service);

        $this->assertStringContainsString(
            "'consent-pdf.disk'",
            $service
        );

        $this->assertStringContainsString(
            'public function diskName(): string',
            $service
        );

        $this->assertStringContainsString(
            'Storage::disk($this->diskName())->put(',
            $service
        );

        $this->assertStringNotContainsString(
            "Storage::disk('local')",
            $service
        );
    }

    public function test_pdf_download_uses_configured_disk(): void
    {
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/'
                .'ConsentPdfController.php'
            )
        );

        $this->assertIsString($controller);

        $this->assertStringContainsString(
            'StreamedResponse',
            $controller
        );

        $this->assertStringContainsString(
            'Storage::disk(',
            $controller
        );

        $this->assertStringContainsString(
            '$pdfDisk',
            $controller
        );

        $this->assertStringContainsString(
            '->readStream(',
            $controller
        );

        $this->assertStringContainsString(
            'response()->streamDownload(',
            $controller
        );

        $this->assertStringNotContainsString(
            'Storage::disk($pdfDisk)->exists(',
            $controller
        );

        $this->assertStringNotContainsString(
            'Storage::disk($pdfDisk)->download(',
            $controller
        );

        $this->assertStringNotContainsString(
            "Storage::disk('local')->path(",
            $controller
        );
    }

    public function test_email_attachment_uses_pdf_service_disk(): void
    {
        $mail = file_get_contents(
            app_path(
                'Mail/ConsentCompletedMail.php'
            )
        );

        $this->assertIsString($mail);

        $this->assertStringContainsString(
            '$pdfService->diskName()',
            $mail
        );
    }

    public function test_bulk_export_reads_remote_pdf_stream(): void
    {
        $bulk = file_get_contents(
            app_path(
                'Http/Controllers/'
                .'ConsentBulkDownloadController.php'
            )
        );

        $this->assertIsString($bulk);

        $this->assertStringContainsString(
            "'consent-pdf.disk'",
            $bulk
        );

        $this->assertStringContainsString(
            '$disk->readStream(',
            $bulk
        );

        $this->assertStringContainsString(
            'tempnam(',
            $bulk
        );

        /*
         * ZIP assembly remains request-scoped local temporary storage.
         */
        $this->assertStringContainsString(
            "Storage::disk('local')->makeDirectory(",
            $bulk
        );
    }
}
