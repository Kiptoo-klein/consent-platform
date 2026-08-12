<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyUrlGenerationTest extends TestCase
{
    public function test_forwarded_public_host_is_used_for_absolute_route_urls(): void
    {
        Route::get(
            '/__tests/proxy-url',
            fn () => route(
                'public-signing-stations.show',
                'proxy-test-token'
            )
        );

        $this
            ->withServerVariables([
                'REMOTE_ADDR' =>
                    '10.0.0.10',

                'HTTP_HOST' =>
                    'consent-platform-production-gai485.laravel.cloud',

                'HTTP_X_FORWARDED_HOST' =>
                    'econsent.site',

                'HTTP_X_FORWARDED_PROTO' =>
                    'https',

                'HTTP_X_FORWARDED_PORT' =>
                    '443',
            ])
            ->get('/__tests/proxy-url')
            ->assertOk()
            ->assertSeeText(
                'https://econsent.site/station/proxy-test-token'
            );
    }
}
