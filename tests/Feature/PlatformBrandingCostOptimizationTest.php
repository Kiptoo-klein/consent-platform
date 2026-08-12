<?php

namespace Tests\Feature;

use App\Services\PlatformBrandingSettingsService;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class PlatformBrandingCostOptimizationTest extends TestCase
{
    public function test_public_branding_url_does_not_probe_remote_object_metadata(): void
    {
        config()->set(
            'platform-branding.disk',
            'branding-cost-test'
        );

        config()->set(
            'filesystems.disks.branding-cost-test.driver',
            's3'
        );

        $disk = Mockery::mock();

        $disk
            ->shouldNotReceive('exists');

        $disk
            ->shouldNotReceive('lastModified');

        $disk
            ->shouldReceive('url')
            ->once()
            ->with(
                'platform-branding/logos/test-logo.png'
            )
            ->andReturn(
                'https://cdn.example.test/platform-branding/logos/test-logo.png'
            );

        Storage::shouldReceive('disk')
            ->once()
            ->with('branding-cost-test')
            ->andReturn($disk);

        $service =
            app(
                PlatformBrandingSettingsService::class
            );

        $method =
            new ReflectionMethod(
                $service,
                'publicUrl'
            );

        $method->setAccessible(true);

        $this->assertSame(
            'https://cdn.example.test/platform-branding/logos/test-logo.png',
            $method->invoke(
                $service,
                'platform-branding/logos/test-logo.png'
            )
        );
    }

    public function test_local_branding_url_needs_no_filesystem_lookup(): void
    {
        config()->set(
            'platform-branding.disk',
            'branding-local-test'
        );

        config()->set(
            'filesystems.disks.branding-local-test.driver',
            'local'
        );

        Storage::shouldReceive('disk')
            ->never();

        $service =
            app(
                PlatformBrandingSettingsService::class
            );

        $method =
            new ReflectionMethod(
                $service,
                'publicUrl'
            );

        $method->setAccessible(true);

        $this->assertSame(
            '/storage/platform-branding/logos/test-logo.png',
            $method->invoke(
                $service,
                'platform-branding/logos/test-logo.png'
            )
        );
    }
}
