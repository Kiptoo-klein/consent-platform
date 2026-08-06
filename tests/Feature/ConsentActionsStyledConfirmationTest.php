<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConsentActionsStyledConfirmationTest extends TestCase
{
    public function test_template_archive_and_restore_use_styled_confirmations(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/consent-templates/'
                .'manage.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-template-archive-confirmation',
            'data-template-restore-confirmation',
            'archive-template-{{ $consentTemplate->id }}',
            'restore-template-{{ $consentTemplate->id }}',
            'Archive consent template?',
            'Restore consent template?',
            'confirm-text="Archive template"',
            'confirm-text="Restore template"',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $view
            );
        }

        $this->assertStringNotContainsString(
            "onsubmit=\"return confirm('Archive this consent template?",
            $view
        );

        $this->assertStringNotContainsString(
            "onsubmit=\"return confirm('Restore this consent template",
            $view
        );
    }

    public function test_consent_record_cancellation_uses_a_styled_confirmation(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/consent-sessions/'
                .'show.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-consent-record-cancel-confirmation',
            'cancel-consent-record-{{ $consentSession->id }}',
            'Cancel this consent record?',
            'confirm-text="Cancel consent record"',
            'variant="danger"',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $view
            );
        }

        $this->assertStringNotContainsString(
            "onsubmit=\"return confirm('Cancel this consent record?",
            $view
        );
    }

    public function test_signed_pdf_delivery_uses_a_styled_confirmation(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/consent-sessions/partials/'
                .'signed-pdf-delivery-panel.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-signed-pdf-delivery-confirmation',
            'signed-pdf-delivery-{{ $consentSession->id }}',
            'Send another signed PDF copy?',
            'Queue signed PDF delivery?',
            'Send another copy',
            'Queue PDF delivery',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $view
            );
        }

        $this->assertStringNotContainsString(
            'onsubmit="return confirm',
            $view
        );
    }
}
