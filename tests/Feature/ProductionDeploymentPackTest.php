<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionDeploymentPackTest extends TestCase
{
    public function test_production_environment_template_uses_secure_postgres_defaults(): void
    {
        $environment = $this->deploymentFile(
            '.env.production.example'
        );

        $this->assertStringContainsString(
            'APP_ENV=production',
            $environment
        );

        $this->assertStringContainsString(
            'APP_DEBUG=false',
            $environment
        );

        $this->assertStringContainsString(
            'DB_CONNECTION=pgsql',
            $environment
        );

        $this->assertStringContainsString(
            'QUEUE_CONNECTION=database',
            $environment
        );

        $this->assertStringContainsString(
            'SESSION_SECURE_COOKIE=true',
            $environment
        );

        $this->assertStringContainsString(
            'SECURITY_FORCE_HTTPS=true',
            $environment
        );

        $this->assertStringNotContainsString(
            'APP_KEY=base64:',
            $environment
        );
    }

    public function test_nginx_exposes_only_public_and_uses_replaceable_php_socket(): void
    {
        $nginx = $this->deploymentFile(
            'nginx-consent-platform.conf.example'
        );

        $this->assertStringContainsString(
            'root APP_PATH/public;',
            $nginx
        );

        $this->assertStringContainsString(
            'fastcgi_pass unix:PHP_FPM_SOCKET;',
            $nginx
        );

        $this->assertStringNotContainsString(
            'php8.3-fpm.sock',
            $nginx
        );

        $this->assertStringContainsString(
            'location ~ /\.(?!well-known).*',
            $nginx
        );
    }

    public function test_queue_worker_and_scheduler_templates_are_present(): void
    {
        $supervisor = $this->deploymentFile(
            'supervisor-consent-platform.conf.example'
        );

        $cron = $this->deploymentFile(
            'cron-consent-platform.txt'
        );

        $this->assertStringContainsString(
            'queue:work database',
            $supervisor
        );

        $this->assertStringContainsString(
            '--queue=emails,default',
            $supervisor
        );

        $this->assertStringContainsString(
            'artisan schedule:run',
            $cron
        );
    }

    public function test_deployment_script_supports_initial_and_repeat_deployments(): void
    {
        $script = $this->deploymentFile(
            'production-deploy.sh'
        );

        $backupPosition = strpos(
            $script,
            'artisan production:backup'
        );

        $maintenancePosition = strpos(
            $script,
            'artisan down'
        );

        $this->assertNotFalse(
            $backupPosition
        );

        $this->assertNotFalse(
            $maintenancePosition
        );

        $this->assertLessThan(
            $maintenancePosition,
            $backupPosition
        );

        $this->assertStringContainsString(
            'INITIAL_DEPLOY',
            $script
        );

        $this->assertStringContainsString(
            'artisan migrate',
            $script
        );

        $this->assertStringContainsString(
            '--isolated',
            $script
        );

        $this->assertStringContainsString(
            'artisan optimize',
            $script
        );

        $this->assertStringContainsString(
            'artisan queue:restart',
            $script
        );

        $this->assertStringContainsString(
            'artisan production:check',
            $script
        );
    }

    public function test_restore_document_matches_postgres_backup_format(): void
    {
        $restore = $this->deploymentFile(
            'RESTORE-DATABASE.md'
        );

        $this->assertStringContainsString(
            'database.sql',
            $restore
        );

        $this->assertStringContainsString(
            'manifest.json',
            $restore
        );

        $this->assertStringContainsString(
            '--set=ON_ERROR_STOP=1',
            $restore
        );

        $this->assertStringNotContainsString(
            'database.sqlite',
            $restore
        );
    }

    private function deploymentFile(
        string $name
    ): string {
        $path = base_path(
            'deploy/'.$name
        );

        $this->assertFileExists(
            $path
        );

        $contents = file_get_contents(
            $path
        );

        $this->assertIsString(
            $contents
        );

        return $contents;
    }
}
