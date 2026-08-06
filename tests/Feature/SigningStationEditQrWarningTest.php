<?php

namespace Tests\Feature;

use Tests\TestCase;

class SigningStationEditQrWarningTest extends TestCase
{
    public function test_edit_page_clearly_warns_about_qr_invalidation(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/signing-stations/edit.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'Warning: changing this kiosk creates a new QR code',
            'current public kiosk link stops',
            'printed QR poster',
            'Current kiosk-device leases are released',
            'fresh 24-hour QR acceptance window',
            'Existing completed consent records are',
            'data-qr-change-confirmation',
            'window.confirm',
            'snapshot() === original',
        ] as $expectedText) {
            $this->assertStringContainsString(
                $expectedText,
                $view
            );
        }
    }
}
