<?php

namespace Tests\Feature;

use App\Models\ConsentSession;
use App\Services\SignedConsentPdfDeliveryService;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class SignedConsentPdfStorageCostOptimizationTest extends TestCase
{
    public function test_canonical_pdf_read_does_not_issue_exists_probe(): void
    {
        config()->set(
            'consent-pdf.disk',
            'cost-pdf-test'
        );

        $path =
            'consent-records/2026/08/12/test.pdf';

        $disk = Mockery::mock();

        $disk
            ->shouldNotReceive('exists');

        $disk
            ->shouldReceive('get')
            ->once()
            ->with($path)
            ->andReturn(
                '%PDF-1.4 test document'
            );

        Storage::shouldReceive('disk')
            ->once()
            ->with('cost-pdf-test')
            ->andReturn($disk);

        $session =
            new ConsentSession();

        $session->pdf_path = $path;

        $service =
            app(
                SignedConsentPdfDeliveryService::class
            );

        $method =
            new ReflectionMethod(
                $service,
                'readConfiguredPdfPath'
            );

        $method->setAccessible(true);

        $result =
            $method->invoke(
                $service,
                $session
            );

        $this->assertIsArray($result);

        $this->assertSame(
            '%PDF-1.4 test document',
            $result['data']
        );

        $this->assertSame(
            'cost-pdf-test:'.$path,
            $result['source']
        );
    }
}
