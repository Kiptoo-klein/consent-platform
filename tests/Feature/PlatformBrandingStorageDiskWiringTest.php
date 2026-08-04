<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlatformBrandingStorageDiskWiringTest extends TestCase
{
    public function test_platform_branding_uses_configurable_storage(): void
    {
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/Platform/'
                .'PlatformBrandingSettingsController.php'
            )
        );

        $service = file_get_contents(
            app_path(
                'Services/'
                .'PlatformBrandingSettingsService.php'
            )
        );

        $this->assertIsString($controller);
        $this->assertIsString($service);

        $this->assertSame(
            'public',
            config('platform-branding.disk')
        );

        $this->assertStringContainsString(
            "config(\n"
            ."            'platform-branding.disk'",
            $controller
        );

        $this->assertStringContainsString(
            "config(\n"
            ."            'platform-branding.disk'",
            $service
        );

        $this->assertStringNotContainsString(
            "Storage::disk('public')",
            $controller
        );

        $this->assertStringNotContainsString(
            "Storage::disk('public')",
            $service
        );

        $this->assertStringContainsString(
            '$disk->url(',
            $service
        );
    }
}
