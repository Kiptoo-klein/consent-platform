<?php

namespace Tests\Feature;

use Tests\TestCase;

class SubscriptionLimitUxWiringTest extends TestCase
{
    public function test_capacity_service_exposes_customer_safe_snapshots(): void
    {
        $service = file_get_contents(
            app_path(
                'Services/SubscriptionUsageLimitService.php'
            )
        );

        $this->assertIsString($service);
        $this->assertStringContainsString(
            'public function signedConsentCapacity(',
            $service
        );
        $this->assertStringContainsString(
            'public function activeKioskCapacity(',
            $service
        );
        $this->assertStringContainsString(
            'public function assertSignedConsentCreationAvailable(',
            $service
        );
    }

    public function test_kiosk_limit_is_checked_before_creation_form(): void
    {
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/SigningStationController.php'
            )
        );

        $this->assertIsString($controller);
        $this->assertStringContainsString(
            "'signing-stations.limit-reached'",
            $controller
        );
        $this->assertStringContainsString(
            'activeKioskCapacity(',
            $controller
        );

        $this->assertFileExists(
            resource_path(
                'views/signing-stations/limit-reached.blade.php'
            )
        );
    }

    public function test_public_signers_use_generic_capacity_pages(): void
    {
        $stationController = file_get_contents(
            app_path(
                'Http/Controllers/PublicSigningStationController.php'
            )
        );

        $consentController = file_get_contents(
            app_path(
                'Http/Controllers/PublicConsentSigningController.php'
            )
        );

        $this->assertIsString($stationController);
        $this->assertIsString($consentController);

        $this->assertStringContainsString(
            'public-signing-stations.signed-consent-limit-reached',
            $stationController
        );

        $this->assertStringContainsString(
            'public-consent.signed-consent-limit-reached',
            $consentController
        );

        $this->assertStringContainsString(
            'assertSignedConsentSlotAvailableLocked',
            $consentController
        );
    }

    public function test_organization_creation_forms_show_capacity(): void
    {
        foreach ([
            'views/consent-campaigns/create.blade.php',
            'views/consent-sessions/create.blade.php',
            'views/consent-templates/create-individual-wizard.blade.php',
        ] as $view) {
            $contents = file_get_contents(
                resource_path($view)
            );

            $this->assertIsString($contents);
            $this->assertStringContainsString(
                '<x-signed-consent-capacity',
                $contents
            );
        }
    }
}
