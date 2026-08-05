<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PlatformOrganizationSupportWiringTest extends TestCase
{
    public function test_platform_support_routes_are_registered(): void
    {
        $expected = [
            'platform.organizations.support.index',
            'platform.organizations.support.consent-records.index',
            'platform.organizations.support.consent-records.download',
            'platform.organizations.support.users.password-reset',
        ];

        foreach ($expected as $name) {
            $this->assertNotNull(
                Route::getRoutes()->getByName(
                    $name
                ),
                "Missing route: {$name}"
            );
        }
    }

    public function test_support_actions_require_reasons_and_are_audited(): void
    {
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/Platform/'
                .'PlatformOrganizationSupportController.php'
            )
        );

        $this->assertIsString(
            $controller
        );

        $this->assertStringContainsString(
            "'support_reason'",
            $controller
        );

        $this->assertStringContainsString(
            'consent.platform_support_pdf_downloaded',
            $controller
        );

        $this->assertStringContainsString(
            'support.password_reset_link_sent',
            $controller
        );

        $this->assertStringContainsString(
            'Password::sendResetLink',
            $controller
        );

        $this->assertStringContainsString(
            'Storage::disk($diskName)',
            $controller
        );
    }

    public function test_support_views_exist(): void
    {
        $this->assertFileExists(
            resource_path(
                'views/platform/organizations/'
                .'support/index.blade.php'
            )
        );

        $this->assertFileExists(
            resource_path(
                'views/platform/organizations/'
                .'support/consent-records.blade.php'
            )
        );
    }
}
