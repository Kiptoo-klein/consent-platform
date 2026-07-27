<?php

namespace App\Http\Controllers;

use App\Http\Middleware\HardenSignatureSubmission;
use App\Http\Middleware\SecurityHardeningMiddleware;
use App\Http\Middleware\ValidateConsentToken;
use App\Http\Middleware\ValidateStationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class SecurityStatusController extends Controller
{
    public function index(
        Request $request
    ): View {
        $isProduction =
            app()->environment('production');

        $completeMiddleware =
            $this->routeMiddleware(
                'public-consent.complete'
            );

        $stationMiddleware =
            $this->routeMiddleware(
                'public-signing-stations.show'
            );

        $consentMiddleware =
            $this->routeMiddleware(
                'public-consent.show'
            );

        $checks = collect([
            $this->check(
                'Application',
                'Application key',
                filled(config('app.key'))
                    ? 'pass'
                    : 'fail',
                filled(config('app.key'))
                    ? 'An application encryption key is configured.'
                    : 'APP_KEY is missing.'
            ),

            $this->check(
                'Application',
                'Production environment',
                $isProduction
                    ? 'pass'
                    : 'warning',
                $isProduction
                    ? 'APP_ENV is production.'
                    : 'The application is currently running outside production.'
            ),

            $this->check(
                'Application',
                'Debug mode',
                config('app.debug')
                    ? (
                        $isProduction
                            ? 'fail'
                            : 'warning'
                    )
                    : 'pass',
                config('app.debug')
                    ? 'Detailed error output is enabled.'
                    : 'Detailed error output is disabled.'
            ),

            $this->check(
                'HTTPS and cookies',
                'HTTPS application URL',
                str_starts_with(
                    strtolower(
                        (string) config(
                            'app.url'
                        )
                    ),
                    'https://'
                )
                    ? 'pass'
                    : (
                        $isProduction
                            ? 'fail'
                            : 'warning'
                    ),
                (string) config(
                    'app.url',
                    'Not configured'
                )
            ),

            $this->check(
                'HTTPS and cookies',
                'Force HTTPS',
                config(
                    'security-hardening.force_https'
                )
                    ? 'pass'
                    : (
                        $isProduction
                            ? 'warning'
                            : 'warning'
                    ),
                config(
                    'security-hardening.force_https'
                )
                    ? 'Application URLs are forced to HTTPS.'
                    : 'Enable SECURITY_FORCE_HTTPS after the live TLS certificate is working.'
            ),

            $this->check(
                'HTTPS and cookies',
                'Secure session cookie',
                config('session.secure')
                    ? 'pass'
                    : (
                        $isProduction
                            ? 'fail'
                            : 'warning'
                    ),
                config('session.secure')
                    ? 'The session cookie is restricted to HTTPS.'
                    : 'SESSION_SECURE_COOKIE is not enabled.'
            ),

            $this->check(
                'HTTPS and cookies',
                'HTTP-only session cookie',
                config(
                    'session.http_only',
                    true
                )
                    ? 'pass'
                    : 'fail',
                config(
                    'session.http_only',
                    true
                )
                    ? 'Browser scripts cannot read the session cookie.'
                    : 'The session cookie is accessible to browser scripts.'
            ),

            $this->check(
                'HTTPS and cookies',
                'SameSite cookie policy',
                in_array(
                    strtolower(
                        (string) config(
                            'session.same_site'
                        )
                    ),
                    ['lax', 'strict'],
                    true
                )
                    ? 'pass'
                    : 'warning',
                'Current value: '
                    .(
                        config(
                            'session.same_site'
                        )
                        ?: 'not configured'
                    )
            ),

            $this->check(
                'Request protection',
                'Trusted hosts',
                count(
                    config(
                        'security-hardening.allowed_hosts',
                        []
                    )
                ) > 0
                    ? 'pass'
                    : (
                        $isProduction
                            ? 'warning'
                            : 'warning'
                    ),
                count(
                    config(
                        'security-hardening.allowed_hosts',
                        []
                    )
                ) > 0
                    ? 'Unexpected host names are rejected.'
                    : 'Add the live domain to SECURITY_ALLOWED_HOSTS before launch.'
            ),

            $this->check(
                'Request protection',
                'Global security middleware',
                $this->fileContains(
                    'bootstrap/app.php',
                    'SECURITY_HARDENING_WEB_MIDDLEWARE'
                )
                    ? 'pass'
                    : 'fail',
                'Security headers and organization boundary checks.'
            ),

            $this->check(
                'Request protection',
                'Public station token validation',
                in_array(
                    ValidateStationToken::class,
                    $stationMiddleware,
                    true
                )
                    ? 'pass'
                    : 'fail',
                'Malformed station tokens are rejected before database processing.'
            ),

            $this->check(
                'Request protection',
                'Consent token validation',
                in_array(
                    ValidateConsentToken::class,
                    $consentMiddleware,
                    true
                )
                    ? 'pass'
                    : 'fail',
                'Malformed consent UUIDs are rejected.'
            ),

            $this->check(
                'Request protection',
                'Public route rate limits',
                $this->hasThrottle(
                    $stationMiddleware
                )
                && $this->hasThrottle(
                    $completeMiddleware
                )
                    ? 'pass'
                    : 'fail',
                'Station, consent and signature routes are throttled.'
            ),

            $this->check(
                'Signature protection',
                'Strict signature validation',
                in_array(
                    HardenSignatureSubmission::class,
                    $completeMiddleware,
                    true
                )
                    ? 'pass'
                    : 'fail',
                'PNG format, base64 data, byte size and dimensions are checked.'
            ),

            $this->check(
                'Signature protection',
                'Duplicate submission protection',
                in_array(
                    HardenSignatureSubmission::class,
                    $completeMiddleware,
                    true
                )
                    ? 'pass'
                    : 'fail',
                'Completed and cancelled records cannot be resubmitted.'
            ),

            $this->check(
                'Browser protection',
                'Content Security Policy',
                match (
                    strtolower(
                        (string) config(
                            'security-hardening.csp_mode'
                        )
                    )
                ) {
                    'enforce' => 'pass',
                    'report-only' => 'warning',
                    default => 'fail',
                },
                'Current mode: '
                    .(
                        config(
                            'security-hardening.csp_mode'
                        )
                        ?: 'off'
                    )
            ),

            $this->check(
                'Authentication',
                'Login rate limiting',
                $this->fileContains(
                    'app/Http/Requests/Auth/LoginRequest.php',
                    'RateLimiter::tooManyAttempts'
                )
                    ? 'pass'
                    : 'warning',
                'Checks the Laravel authentication request for brute-force protection.'
            ),

            $this->check(
                'Queues and cleanup',
                'Background queue',
                config('queue.default')
                    !== 'sync'
                    ? 'pass'
                    : (
                        $isProduction
                            ? 'warning'
                            : 'warning'
                    ),
                'Current connection: '
                    .config(
                        'queue.default',
                        'not configured'
                    )
            ),

            $this->check(
                'Queues and cleanup',
                'Failed-job storage',
                Schema::hasTable(
                    'failed_jobs'
                )
                    ? 'pass'
                    : 'warning',
                Schema::hasTable(
                    'failed_jobs'
                )
                    ? 'Failed jobs can be inspected and pruned.'
                    : 'The failed_jobs table was not found.'
            ),

            $this->check(
                'Queues and cleanup',
                'Scheduled cleanup',
                $this->fileContains(
                    'routes/console.php',
                    'SECURITY_HARDENING_CLEANUP_SCHEDULE'
                )
                    ? 'pass'
                    : 'fail',
                'Stale kiosk sessions, old failed jobs and expired sessions are cleaned automatically.'
            ),
        ]);

        $runtime = $this->runtimeMetrics();

        $latestFailedJobs =
            $this->latestFailedJobs();

        return view(
            'security.status',
            [
                'checks' => $checks,
                'summary' => [
                    'pass' =>
                        $checks
                            ->where(
                                'status',
                                'pass'
                            )
                            ->count(),

                    'warning' =>
                        $checks
                            ->where(
                                'status',
                                'warning'
                            )
                            ->count(),

                    'fail' =>
                        $checks
                            ->where(
                                'status',
                                'fail'
                            )
                            ->count(),
                ],
                'runtime' => $runtime,
                'latestFailedJobs' =>
                    $latestFailedJobs,
                'isProduction' =>
                    $isProduction,
            ]
        );
    }

    private function check(
        string $category,
        string $label,
        string $status,
        string $details
    ): array {
        return compact(
            'category',
            'label',
            'status',
            'details'
        );
    }

    private function routeMiddleware(
        string $routeName
    ): array {
        foreach (
            Route::getRoutes() as $route
        ) {
            if (
                $route->getName()
                === $routeName
            ) {
                return $route
                    ->gatherMiddleware();
            }
        }

        return [];
    }

    private function hasThrottle(
        array $middleware
    ): bool {
        return collect($middleware)
            ->contains(
                fn ($item) =>
                    is_string($item)
                    && str_starts_with(
                        $item,
                        'throttle:'
                    )
            );
    }

    private function fileContains(
        string $relativePath,
        string $needle
    ): bool {
        $path = base_path(
            $relativePath
        );

        if (! is_file($path)) {
            return false;
        }

        return str_contains(
            (string) file_get_contents(
                $path
            ),
            $needle
        );
    }

    private function runtimeMetrics(): array
    {
        $metrics = [
            'queued_jobs' => null,
            'failed_jobs' => null,
            'stale_kiosk_sessions' => null,
            'invalid_station_tokens' => null,
            'invalid_consent_tokens' => null,
        ];

        try {
            if (Schema::hasTable('jobs')) {
                $metrics['queued_jobs'] =
                    DB::table('jobs')->count();
            }

            if (
                Schema::hasTable(
                    'failed_jobs'
                )
            ) {
                $metrics['failed_jobs'] =
                    DB::table(
                        'failed_jobs'
                    )->count();
            }

            if (
                Schema::hasTable(
                    'consent_sessions'
                )
            ) {
                $cutoff = now()->subHours(
                    max(
                        1,
                        (int) config(
                            'security-hardening.cleanup.stale_kiosk_hours',
                            24
                        )
                    )
                );

                $metrics[
                    'stale_kiosk_sessions'
                ] = DB::table(
                    'consent_sessions'
                )
                    ->whereNotNull(
                        'signing_station_id'
                    )
                    ->whereIn(
                        'status',
                        [
                            'pending',
                            'in_progress',
                        ]
                    )
                    ->where(
                        'updated_at',
                        '<',
                        $cutoff
                    )
                    ->count();

                $metrics[
                    'invalid_consent_tokens'
                ] = DB::table(
                    'consent_sessions'
                )
                    ->where(
                        function ($query) {
                            $query
                                ->whereNull(
                                    'access_token'
                                )
                                ->orWhereRaw(
                                    'LENGTH(access_token) <> 36'
                                );
                        }
                    )
                    ->count();
            }

            if (
                Schema::hasTable(
                    'signing_stations'
                )
            ) {
                $metrics[
                    'invalid_station_tokens'
                ] = DB::table(
                    'signing_stations'
                )
                    ->where(
                        function ($query) {
                            $query
                                ->whereNull(
                                    'station_token'
                                )
                                ->orWhereRaw(
                                    'LENGTH(station_token) < 40'
                                );
                        }
                    )
                    ->count();
            }
        } catch (Throwable) {
            // The page remains available even if
            // one optional runtime query fails.
        }

        return $metrics;
    }

    private function latestFailedJobs(): Collection
    {
        if (
            ! Schema::hasTable(
                'failed_jobs'
            )
        ) {
            return collect();
        }

        try {
            return DB::table(
                'failed_jobs'
            )
                ->select([
                    'uuid',
                    'connection',
                    'queue',
                    'exception',
                    'failed_at',
                ])
                ->orderByDesc(
                    'failed_at'
                )
                ->limit(5)
                ->get()
                ->map(
                    function ($job) {
                        $firstLine = trim(
                            strtok(
                                (string) $job
                                    ->exception,
                                "\n"
                            ) ?: ''
                        );

                        return (object) [
                            'uuid' => $job->uuid,
                            'connection' =>
                                $job->connection,
                            'queue' => $job->queue,
                            'exception' =>
                                mb_strimwidth(
                                    $firstLine,
                                    0,
                                    180,
                                    '…'
                                ),
                            'failed_at' =>
                                (string) $job
                                    ->failed_at,
                        ];
                    }
                );
        } catch (Throwable) {
            return collect();
        }
    }
}
