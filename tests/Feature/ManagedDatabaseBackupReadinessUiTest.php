<?php

namespace Tests\Feature;

use Tests\TestCase;

class ManagedDatabaseBackupReadinessUiTest extends TestCase
{
    public function test_readiness_controller_exposes_managed_backup_state(): void
    {
        $controller =
            file_get_contents(
                app_path(
                    'Http/Controllers/Platform/'
                    .'ProductionReadinessController.php'
                )
            );

        $this->assertIsString($controller);

        $this->assertStringContainsString(
            "'managedDatabaseBackups'",
            $controller
        );

        $this->assertStringContainsString(
            "'production-readiness.backups.managed_database'",
            $controller
        );
    }

    public function test_managed_backups_disable_web_local_backup_action(): void
    {
        $controller =
            file_get_contents(
                app_path(
                    'Http/Controllers/Platform/'
                    .'ProductionReadinessController.php'
                )
            );

        $this->assertStringContainsString(
            'Database backups are managed by the hosting platform.',
            $controller
        );
    }

    public function test_readiness_view_explains_managed_backups(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/platform/production-readiness/index.blade.php'
                )
            );

        $this->assertIsString($view);

        $this->assertStringContainsString(
            'Managed database backup',
            $view
        );

        $this->assertStringContainsString(
            'Managed backups enabled',
            $view
        );

        $this->assertStringContainsString(
            '@unless ($managedDatabaseBackups)',
            $view
        );
    }
}
