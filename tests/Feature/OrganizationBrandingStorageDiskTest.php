<?php

namespace Tests\Feature;

use Tests\TestCase;

class OrganizationBrandingStorageDiskTest extends TestCase
{
    public function test_organization_branding_uses_configurable_disk(): void
    {
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/'
                .'OrganizationBrandingController.php'
            )
        );

        $view = file_get_contents(
            resource_path(
                'views/organization-branding/edit.blade.php'
            )
        );

        $pdf = file_get_contents(
            resource_path(
                'views/pdfs/consent-record.blade.php'
            )
        );

        $this->assertIsString($controller);
        $this->assertIsString($view);
        $this->assertIsString($pdf);

        $this->assertStringContainsString(
            "'organization-branding.disk'",
            $controller
        );

        $this->assertStringContainsString(
            '$brandingDisk',
            $controller
        );

        $this->assertStringNotContainsString(
            "->store('organization-logos', 'public')",
            $controller
        );

        $this->assertStringContainsString(
            "'organization-branding.disk'",
            $view
        );

        $this->assertStringContainsString(
            ')->url($organization->logo)',
            $view
        );

        $this->assertStringContainsString(
            "'organization-branding.disk'",
            $pdf
        );
    }
}
