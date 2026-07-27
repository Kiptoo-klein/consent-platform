<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class SecurityHardeningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (
            (bool) config(
                'security-hardening.force_https',
                false
            )
        ) {
            URL::forceScheme('https');
        }

        RateLimiter::for(
            'public-station-view',
            fn (Request $request) =>
                Limit::perMinute(
                    $this->limit(
                        'station_views_per_minute',
                        180
                    )
                )->by(
                    $this->routeKey(
                        $request,
                        'stationToken'
                    )
                )
        );

        RateLimiter::for(
            'public-station-action',
            fn (Request $request) =>
                Limit::perMinute(
                    $this->limit(
                        'station_actions_per_minute',
                        30
                    )
                )->by(
                    $this->routeKey(
                        $request,
                        'stationToken'
                    )
                )
        );

        RateLimiter::for(
            'public-consent-view',
            fn (Request $request) =>
                Limit::perMinute(
                    $this->limit(
                        'consent_views_per_minute',
                        120
                    )
                )->by(
                    $this->routeKey(
                        $request,
                        'accessToken'
                    )
                )
        );

        RateLimiter::for(
            'public-consent-write',
            fn (Request $request) =>
                Limit::perMinute(
                    $this->limit(
                        'consent_updates_per_minute',
                        30
                    )
                )->by(
                    $this->routeKey(
                        $request,
                        'accessToken'
                    )
                )
        );

        RateLimiter::for(
            'public-signature-submit',
            fn (Request $request) =>
                Limit::perMinute(
                    $this->limit(
                        'signature_submissions_per_minute',
                        5
                    )
                )->by(
                    $this->routeKey(
                        $request,
                        'accessToken'
                    )
                )
        );
    }

    private function limit(
        string $name,
        int $fallback
    ): int {
        return max(
            1,
            (int) config(
                "security-hardening.limits.{$name}",
                $fallback
            )
        );
    }

    private function routeKey(
        Request $request,
        string $routeParameter
    ): string {
        return hash(
            'sha256',
            implode('|', [
                $request->ip(),
                (string) $request->route(
                    $routeParameter
                ),
            ])
        );
    }
}
