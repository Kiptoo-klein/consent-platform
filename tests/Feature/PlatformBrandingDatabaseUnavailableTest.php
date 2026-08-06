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
         * Clear the normal test cache while its database is still
         * available.
         */
        $service->forgetCache();

        $originalConnection =
            (string) config(
                'database.default'
            );

        $temporaryConnection =
            'branding_unavailable';

        $sqliteConfiguration =
            config(
                'database.connections.sqlite',
                []
            );

        $missingDatabase =
            database_path(
                'missing-cloud-build-'
                .bin2hex(random_bytes(6))
                .'.sqlite'
            );

        /*
         * Use a separate connection so the RefreshDatabase
         * transaction on the normal in-memory SQLite connection
         * remains intact for subsequent tests.
         */
        config([
            'database.connections.'
                .$temporaryConnection =>
                    array_merge(
                        is_array($sqliteConfiguration)
                            ? $sqliteConfiguration
                            : [],
                        [
                            'driver' =>
                                'sqlite',

                            'database' =>
                                $missingDatabase,
                        ]
                    ),

            'database.default' =>
                $temporaryConnection,
        ]);

        DB::purge(
            $temporaryConnection
        );

        try {
            $settings =
                $service->settings();

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
            ]);

            DB::purge(
                $temporaryConnection
            );

            config([
                'database.connections.'
                    .$temporaryConnection =>
                        null,
            ]);

            $service->forgetCache();

            if (is_file($missingDatabase)) {
                @unlink($missingDatabase);
            }
        }
    }
}
