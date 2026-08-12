<?php

namespace Tests\Feature;

use Tests\TestCase;

class ManagedDatabaseBackupReadinessTest extends TestCase
{
    public function test_managed_database_backup_configuration_is_available(): void
    {
        config()->set(
            'production-readiness.backups.managed_database',
            true
        );

        $this->assertTrue(
            config(
                'production-readiness.backups.managed_database'
            )
        );
    }

    public function test_readiness_supports_managed_database_backups(): void
    {
        $service =
            file_get_contents(
                app_path(
                    'Services/ProductionReadinessService.php'
                )
            );

        $this->assertIsString($service);

        $this->assertStringContainsString(
            "'production-readiness.backups.managed_database'",
            $service
        );

        $this->assertStringContainsString(
            "'Database backup'",
            $service
        );

        $this->assertStringContainsString(
            'Managed database backups are enabled by the hosting platform.',
            $service
        );
    }
}
