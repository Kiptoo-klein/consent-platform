<?php

namespace Tests\Feature;

use App\Services\PlatformBrandingSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlatformBrandingDatabaseUnavailableTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_uses_defaults_when_database_is_unavailable(): void
    {
        $service = app(
            PlatformBrandingSettingsService::class
        );

        /*
         * Clear the normal test cache while its database is still available.
         */
        $service->forgetCache();

        $originalConnection =
            config('database.default');

        $originalDatabase =
            config(
                'database.connections.sqlite.database'
            );

        $missingDatabase =
            database_path(
                'missing-cloud-build-'
                .bin2hex(random_bytes(6))
                .'.sqlite'
            );

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' =>
                $missingDatabase,
        ]);

        DB::purge('sqlite');

        try {
            $settings = $service->settings();

            $this->assertSame(
                'eConsent',
                $settings['platform_name']
            );

            $this->assertSame(
                'eC',
                $settings['short_name']
            );
        } finally {
            config([
                'database.default' =>
                    $originalConnection,

                'database.connections.sqlite.database' =>
                    $originalDatabase,
            ]);

            DB::purge('sqlite');
            $service->forgetCache();
        }
    }
}
