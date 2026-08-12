<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsentPdfDownloadCostOptimizationTest extends TestCase
{
    public function test_pdf_download_streams_without_remote_exists_probe(): void
    {
        $controller =
            file_get_contents(
                app_path(
                    'Http/Controllers/ConsentPdfController.php'
                )
            );

        $this->assertIsString($controller);

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
    }
}
