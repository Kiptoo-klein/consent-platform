<?php

namespace Tests\Feature;

use Tests\TestCase;

class PermanentEconsentPromotionWiringTest extends TestCase
{
    public function test_all_consent_emails_include_the_permanent_promotion(): void
    {
        $views = [
            resource_path(
                'views/emails/consent-signing-request.blade.php'
            ),
            resource_path(
                'views/emails/signed-consent-copy.blade.php'
            ),
            resource_path(
                'views/emails/consent-completed.blade.php'
            ),
        ];

        foreach ($views as $view) {
            $source = file_get_contents($view);

            $this->assertIsString($source);

            $this->assertStringContainsString(
                "emails.partials.econsent-promotion",
                $source
            );
        }
    }

    public function test_email_promotion_links_to_the_public_site(): void
    {
        $source = file_get_contents(
            resource_path(
                'views/emails/partials/'
                .'econsent-promotion.blade.php'
            )
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'Securely powered by eConsent',
            $source
        );

        $this->assertStringContainsString(
            'https://econsent.site',
            $source
        );
    }

    public function test_new_consent_pdfs_include_the_permanent_footer(): void
    {
        $source = file_get_contents(
            resource_path(
                'views/pdfs/consent-record.blade.php'
            )
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'Securely created with eConsent',
            $source
        );

        $this->assertStringContainsString(
            'https://econsent.site',
            $source
        );

        $this->assertStringContainsString(
            'separate from the consent',
            $source
        );
    }
}
