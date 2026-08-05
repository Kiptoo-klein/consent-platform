<?php

namespace Tests\Feature;

use Tests\TestCase;

class KioskSingleEmailIdentityWiringTest extends TestCase
{
    public function test_kiosk_completion_uses_only_signed_copy_delivery(): void
    {
        $source = file_get_contents(
            app_path('Jobs/GenerateConsentPdfJob.php')
        );

        $this->assertIsString($source);

        $kioskBranch = strpos(
            $source,
            'if (filled($consentSession->signing_station_id))'
        );

        $generalDelivery = strpos(
            $source,
            'No email address was provided.'
        );

        $this->assertNotFalse($kioskBranch);
        $this->assertNotFalse($generalDelivery);
        $this->assertLessThan(
            $generalDelivery,
            $kioskBranch
        );

        $this->assertStringContainsString(
            'SendSignedConsentPdfJob::dispatch(',
            $source
        );

        $this->assertStringNotContainsString(
            'SIGNED PDF AUTO EMAIL HOOK',
            $source
        );
    }

    public function test_kiosk_form_uses_all_organization_email_fallbacks(): void
    {
        $source = file_get_contents(
            resource_path(
                'views/signing-stations/partials/'
                .'email-delivery-settings.blade.php'
            )
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            '$organizationForEmailSettings?->email',
            $source
        );

        $this->assertStringContainsString(
            "'contact_email'",
            $source
        );

        $this->assertStringContainsString(
            '$organizationForEmailSettings?->support_email',
            $source
        );
    }

    public function test_kept_kiosk_email_has_sender_name_and_reply_to(): void
    {
        $source = file_get_contents(
            app_path('Mail/SignedConsentCopyMail.php')
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            '$mail->from($fromAddress, $senderName)',
            $source
        );

        $this->assertStringContainsString(
            '$mail->replyTo($replyTo, $senderName)',
            $source
        );
    }
}
