<?php

namespace Tests\Feature;

use App\Mail\EmailConfigurationTestMail;
use Tests\TestCase;

class EmailConfigurationTestViewTest extends TestCase
{
    public function test_email_configuration_test_mailable_renders(): void
    {
        $this->assertTrue(
            view()->exists(
                'emails.email-configuration-test'
            )
        );

        $mail = new EmailConfigurationTestMail(
            requestedBy: 'Test Administrator',
            deliveryMode: 'queued',
            mailerName: 'resend',
            includeAttachment: false,
        );

        $html = $mail->render();

        $this->assertStringContainsString(
            'Email configuration test',
            $html
        );

        $this->assertStringContainsString(
            'outbound email delivery',
            $html
        );

        $this->assertStringContainsString(
            'Test Administrator',
            $html
        );

        $this->assertStringContainsString(
            'resend',
            $html
        );

        $this->assertStringContainsString(
            'queued',
            $html
        );
    }
}
