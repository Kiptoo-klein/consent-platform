<?php

namespace Tests\Feature;

use Tests\TestCase;

class JavascriptStyledConfirmationTest extends TestCase
{
    public function test_confirmation_component_supports_event_actions_without_breaking_standard_forms(): void
    {
        $component = file_get_contents(
            resource_path(
                'views/components/'
                .'action-confirmation-modal.blade.php'
            )
        );

        $this->assertIsString($component);

        foreach ([
            "'confirmEvent' => null",
            'data-action-confirmation-event',
            '$dispatch(@js($confirmEvent))',
            'data-action-confirmation-submit',
            'type="submit"',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $component
            );
        }
    }

    public function test_signing_station_changes_use_the_styled_qr_warning(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/signing-stations/'
                .'edit.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-signing-station-edit-form',
            'name="signing-station-qr-change"',
            'signing-station-qr-change-confirmed',
            'confirmationGranted',
            'form.requestSubmit()',
            'Current kiosk-device leases will be released',
            'Existing completed consent records will not be deleted',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $view
            );
        }

        $this->assertStringNotContainsString(
            'window.confirm(',
            $view
        );
    }

    public function test_word_import_replacement_uses_the_styled_modal(): void
    {
        $component = file_get_contents(
            resource_path(
                'views/components/'
                .'consent-template-docx-import.blade.php'
            )
        );

        $this->assertIsString($component);

        foreach ([
            'pendingFile: null',
            'replacementConfirmed = false',
            'confirmDocumentReplacement()',
            'docx-import-replacement-confirmed.window',
            'name="replace-consent-document"',
            'confirm-event="docx-import-replacement-confirmed"',
            'Replace the current consent document with the imported Word document?',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $component
            );
        }

        $this->assertStringNotContainsString(
            'window.confirm(',
            $component
        );
    }
}
