<?php

namespace Tests\Feature;

use Tests\TestCase;

class ManageUsersStyledConfirmationTest extends TestCase
{
    public function test_active_organization_user_actions_use_styled_confirmations(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/platform/organizations/users/'
                .'index.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-user-status-confirmation',
            'data-user-archive-confirmation',
            'user-status-{{ $organization->id }}-{{ $user->id }}',
            'archive-user-{{ $organization->id }}-{{ $user->id }}',
            'Disable user account?',
            'Enable user account?',
            'Archive user account?',
            '<x-action-confirmation-modal',
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

    public function test_archived_user_restore_uses_a_styled_confirmation(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/platform/organizations/users/'
                .'archived.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-user-restore-confirmation',
            'restore-user-{{ $organization->id }}-{{ $user->id }}',
            'Restore &amp; Enable',
            'Restore and enable user?',
            'confirm-text="Restore & Enable"',
            'variant="success"',
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

    public function test_platform_staff_status_uses_a_styled_confirmation(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/platform/staff/index.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-platform-staff-status-confirmation',
            'platform-staff-status-{{ $staffMember->id }}',
            'Disable platform staff account?',
            'Enable platform staff account?',
            '<x-action-confirmation-modal',
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
