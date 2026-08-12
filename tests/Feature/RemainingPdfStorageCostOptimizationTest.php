<?php

namespace Tests\Feature;

use Tests\TestCase;

class RemainingPdfStorageCostOptimizationTest extends TestCase
{
    public function test_bulk_export_does_not_probe_pdf_before_streaming(): void
    {
        $controller =
            file_get_contents(
                app_path(
                    'Http/Controllers/'
                    .'ConsentBulkDownloadController.php'
                )
            );

        $this->assertIsString($controller);

        $this->assertStringContainsString(
            '$disk->readStream(',
            $controller
        );

        $this->assertStringNotContainsString(
            '$disk->exists($candidate)',
            $controller
        );
    }

    public function test_platform_support_pdf_download_uses_direct_stream(): void
    {
        $controller =
            file_get_contents(
                app_path(
                    'Http/Controllers/Platform/'
                    .'PlatformOrganizationSupportController.php'
                )
            );

        $this->assertIsString($controller);

        $this->assertStringContainsString(
            'Storage::disk(',
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
            '->exists('
            .PHP_EOL
            .'                    $consentSession->pdf_path',
            $controller
        );
    }
}
