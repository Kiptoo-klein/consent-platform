<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsentRecordUsageBarWiringTest extends TestCase
{
    public function test_consent_records_receive_organization_capacity(): void
    {
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/ConsentSessionController.php'
            )
        );

        $this->assertIsString($controller);

        $this->assertStringContainsString(
            'SubscriptionUsageLimitService $usageLimitService',
            $controller
        );

        $this->assertStringContainsString(
            "'signedConsentCapacity' =>",
            $controller
        );

        $this->assertStringContainsString(
            '->signedConsentCapacity(',
            $controller
        );
    }

    public function test_consent_records_show_the_usage_bar(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/consent-sessions/index.blade.php'
            )
        );

        $this->assertIsString($view);

        $this->assertStringContainsString(
            '<x-signed-consent-capacity',
            $view
        );

        $this->assertStringContainsString(
            ':show-bar="true"',
            $view
        );
    }

    public function test_capacity_component_has_progress_and_source_explanation(): void
    {
        $component = file_get_contents(
            resource_path(
                'views/components/'
                .'signed-consent-capacity.blade.php'
            )
        );

        $this->assertIsString($component);

        $this->assertStringContainsString(
            'aria-label="Signed consent usage"',
            $component
        );

        $this->assertStringContainsString(
            'individual, bulk,',
            $component
        );

        $this->assertStringContainsString(
            'kiosk signing',
            $component
        );

        $this->assertStringContainsString(
            'Unlimited signed consent records',
            $component
        );
    }
}
