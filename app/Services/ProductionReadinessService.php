<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProductionReadinessService
{
    public function checks(): Collection
    {
        $checks = collect();

        $checks->push(
            $this->check(
                'Application',
                'Application encryption key',
                filled(config('app.key'))
                    ? 'pass'
                    : 'fail',
                filled(config('app.key'))
                    ? 'APP_KEY is configured.'
                    : 'APP_KEY is missing.'
            )
        );

        $isProduction =
            app()->environment('production');

        $checks->push(
            $this->check(
                'Application',
                'Application environment',
                $isProduction
                    ? 'pass'
                    : 'warning',
                'Current environment: '
                    .app()->environment()
                    .'. Local mode is acceptable until deployment.'
            )
        );

        $debugEnabled = (bool) config(
            'app.debug'
        );

        $checks->push(
            $this->check(
                'Application',
                'Debug mode',
                $debugEnabled
                    ? (
                        $isProduction
                            ? 'fail'
                            : 'warning'
                    )
                    : 'pass',
                $debugEnabled
                    ? 'Detailed error output is enabled.'
                    : 'Detailed error output is disabled.'
            )
        );

        $appUrl = (string) config(
            'app.url',
            ''
        );

        $host = parse_url(
            $appUrl,
            PHP_URL_HOST
        );

        $placeholderHost =
            $host === null
            || $host === ''
            || in_array(
                $host,
                [
                    '127.0.0.1',
                    'localhost',
                    'example.com',
                    'example.test',
                ],
                true
            )
            || str_ends_with(
                (string) $host,
                '.test'
            );

        $checks->push(
            $this->check(
                'Domain and HTTPS',
                'Public domain',
                $placeholderHost
                    ? 'deferred'
                    : 'pass',
                $placeholderHost
                    ? 'Waiting for the production domain.'
                    : 'Configured host: '.$host
            )
        );

        $https = str_starts_with(
            strtolower($appUrl),
            'https://'
        );

        $checks->push(
            $this->check(
                'Domain and HTTPS',
                'HTTPS application URL',
                $https
                    ? 'pass'
                    : (
                        $placeholderHost
                            ? 'deferred'
                            : 'warning'
                    ),
                $https
                    ? $appUrl
                    : 'HTTPS will be enabled after the domain and certificate are connected.'
            )
        );

        $checks->push(
            $this->check(
                'Domain and HTTPS',
                'Secure session cookies',
                config('session.secure')
                    ? 'pass'
                    : (
                        $placeholderHost
                            ? 'deferred'
                            : 'warning'
                    ),
                config('session.secure')
                    ? 'Session cookies are restricted to HTTPS.'
                    : 'Enable SESSION_SECURE_COOKIE after HTTPS is active.'
            )
        );

        $checks->push(
            $this->check(
                'Application',
                'PHP version',
                version_compare(
                    PHP_VERSION,
                    (string) config(
                        'production-readiness.deployment.minimum_php_version',
                        '8.2.0'
                    ),
                    '>='
                )
                    ? 'pass'
                    : 'fail',
                'Installed: '.PHP_VERSION
                .'; minimum: '
                .config(
                    'production-readiness.deployment.minimum_php_version',
                    '8.2.0'
                )
            )
        );

        $requiredExtensions = [
            'pdo',
            'mbstring',
            'openssl',
            'fileinfo',
            'json',
        ];

        $missingExtensions = collect(
            $requiredExtensions
        )->reject(
            fn (string $extension): bool =>
                extension_loaded($extension)
        );

        $checks->push(
            $this->check(
                'Application',
                'Required PHP extensions',
                $missingExtensions->isEmpty()
                    ? 'pass'
                    : 'fail',
                $missingExtensions->isEmpty()
                    ? 'Required extensions are installed.'
                    : 'Missing: '
                        .$missingExtensions->join(', ')
            )
        );

        try {
            DB::connection()->getPdo();

            $checks->push(
                $this->check(
                    'Database',
                    'Database connection',
                    'pass',
                    'Connected using the '
                        .DB::connection()
                            ->getDriverName()
                        .' driver.'
                )
            );
        } catch (Throwable $exception) {
            $checks->push(
                $this->check(
                    'Database',
                    'Database connection',
                    'fail',
                    'Connection failed: '
                        .$exception->getMessage()
                )
            );
        }

        $migrationStatus =
            $this->migrationStatus();

        $checks->push(
            $this->check(
                'Database',
                'Database migrations',
                $migrationStatus['status'],
                $migrationStatus['details']
            )
        );

        $latestBackup =
            $this->latestBackup();

        if ($latestBackup === null) {
            $checks->push(
                $this->check(
                    'Backups',
                    'Latest backup',
                    'warning',
                    'No production backup has been created yet.'
                )
            );
        } else {
            $warningAge = max(
                1,
                (int) config(
                    'production-readiness.backups.warning_age_hours',
                    36
                )
            );

            $checks->push(
                $this->check(
                    'Backups',
                    'Latest backup',
                    $latestBackup['verified']
                    && $latestBackup['age_hours']
                        <= $warningAge
                        ? 'pass'
                        : 'warning',
                    $latestBackup['created_at']
                    .' — '
                    .$this->formatBytes(
                        $latestBackup['total_size']
                    )
                    .' — '
                    .(
                        $latestBackup['verified']
                            ? 'checksums verified'
                            : 'verification failed'
                    )
                )
            );
        }

        $writablePaths = [
            storage_path(),
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            app()->bootstrapPath('cache'),
        ];

        $unwritable = collect(
            $writablePaths
        )->reject(
            fn (string $path): bool =>
                is_dir($path)
                && is_writable($path)
        );

        $checks->push(
            $this->check(
                'Storage',
                'Writable application directories',
                $unwritable->isEmpty()
                    ? 'pass'
                    : 'fail',
                $unwritable->isEmpty()
                    ? 'Storage and cache directories are writable.'
                    : 'Not writable: '
                        .$unwritable->join(', ')
            )
        );

        $publicStorage = public_path(
            'storage'
        );

        $checks->push(
            $this->check(
                'Storage',
                'Public storage link',
                is_link($publicStorage)
                || file_exists($publicStorage)
                    ? 'pass'
                    : 'warning',
                is_link($publicStorage)
                || file_exists($publicStorage)
                    ? 'The public storage link exists.'
                    : 'Run php artisan storage:link when public files are required.'
            )
        );

        $freeDisk = @disk_free_space(
            storage_path()
        );

        $minimumDisk =
            max(
                1,
                (int) config(
                    'production-readiness.deployment.minimum_free_disk_mb',
                    1024
                )
            ) * 1024 * 1024;

        $checks->push(
            $this->check(
                'Storage',
                'Available disk space',
                is_numeric($freeDisk)
                && $freeDisk >= $minimumDisk
                    ? 'pass'
                    : 'warning',
                is_numeric($freeDisk)
                    ? $this->formatBytes(
                        (int) $freeDisk
                    ).' available.'
                    : 'Disk-space information is unavailable.'
            )
        );

        $queueConnection = (string) config(
            'queue.default',
            'sync'
        );

        $checks->push(
            $this->check(
                'Queue and scheduler',
                'Queue connection',
                $queueConnection === 'sync'
                    ? 'warning'
                    : 'pass',
                'Current connection: '
                    .$queueConnection
                    .'. Production should use database or Redis.'
            )
        );

        $jobsTable = Schema::hasTable(
            'jobs'
        );

        $failedJobsTable =
            Schema::hasTable(
                'failed_jobs'
            );

        $checks->push(
            $this->check(
                'Queue and scheduler',
                'Queue database tables',
                $jobsTable
                && $failedJobsTable
                    ? 'pass'
                    : 'fail',
                $jobsTable
                && $failedJobsTable
                    ? 'jobs and failed_jobs tables exist.'
                    : 'A required queue table is missing.'
            )
        );

        $schedulerHeartbeat =
            $this->heartbeat(
                'scheduler'
            );

        $checks->push(
            $this->check(
                'Queue and scheduler',
                'Scheduler heartbeat',
                $schedulerHeartbeat['status'],
                $schedulerHeartbeat['details']
            )
        );

        $queueHeartbeat =
            $this->heartbeat(
                'queue'
            );

        $checks->push(
            $this->check(
                'Queue and scheduler',
                'Queue-worker heartbeat',
                $queueHeartbeat['status'],
                $queueHeartbeat['details']
            )
        );

        $metrics = $this->runtimeMetrics();

        $maximumQueued = max(
            1,
            (int) config(
                'production-readiness.deployment.maximum_queued_jobs_warning',
                100
            )
        );

        $checks->push(
            $this->check(
                'Queue and scheduler',
                'Queued jobs',
                ($metrics['queued_jobs'] ?? 0)
                    > $maximumQueued
                    ? 'warning'
                    : 'pass',
                $metrics['queued_jobs'] === null
                    ? 'Queue depth is unavailable.'
                    : number_format(
                        $metrics['queued_jobs']
                    ).' job(s) waiting.'
            )
        );

        $checks->push(
            $this->check(
                'Queue and scheduler',
                'Failed jobs',
                ($metrics['failed_jobs'] ?? 0)
                    > 0
                    ? 'warning'
                    : 'pass',
                $metrics['failed_jobs'] === null
                    ? 'Failed-job count is unavailable.'
                    : number_format(
                        $metrics['failed_jobs']
                    ).' failed job(s).'
            )
        );

        $mailer = (string) config(
            'mail.default',
            'array'
        );

        $checks->push(
            $this->check(
                'Email',
                'Live email provider',
                in_array(
                    $mailer,
                    ['array', 'log'],
                    true
                )
                    ? 'deferred'
                    : 'pass',
                in_array(
                    $mailer,
                    ['array', 'log'],
                    true
                )
                    ? 'Email generation is prepared; live delivery waits for the provider and domain.'
                    : 'Configured mailer: '.$mailer
            )
        );

        $cacheStore = (string) config(
            'cache.default',
            'database'
        );

        $checks->push(
            $this->check(
                'Performance',
                'Cache store',
                in_array(
                    $cacheStore,
                    ['array', 'null'],
                    true
                )
                    ? 'warning'
                    : 'pass',
                'Current store: '.$cacheStore
            )
        );

        $checks->push(
            $this->check(
                'Performance',
                'Configuration cache',
                is_file(
                    app()->bootstrapPath(
                        'cache/config.php'
                    )
                )
                    ? 'pass'
                    : (
                        $isProduction
                            ? 'warning'
                            : 'info'
                    ),
                is_file(
                    app()->bootstrapPath(
                        'cache/config.php'
                    )
                )
                    ? 'Configuration is cached.'
                    : 'Run php artisan optimize during deployment.'
            )
        );

        $routeCaches = glob(
            app()->bootstrapPath(
                'cache/routes-*.php'
            )
        ) ?: [];

        $checks->push(
            $this->check(
                'Performance',
                'Route cache',
                count($routeCaches) > 0
                    ? 'pass'
                    : (
                        $isProduction
                            ? 'warning'
                            : 'info'
                    ),
                count($routeCaches) > 0
                    ? 'Routes are cached.'
                    : 'Routes are not currently cached.'
            )
        );

        $compiledViews = glob(
            storage_path(
                'framework/views/*.php'
            )
        ) ?: [];

        $checks->push(
            $this->check(
                'Performance',
                'Compiled views',
                count($compiledViews) > 0
                    ? 'pass'
                    : 'info',
                count($compiledViews) > 0
                    ? number_format(
                        count($compiledViews)
                    ).' compiled view(s).'
                    : 'Views have not been precompiled.'
            )
        );

        $checks->push(
            $this->check(
                'Operations',
                'Maintenance mode',
                app()->isDownForMaintenance()
                    ? 'warning'
                    : 'pass',
                app()->isDownForMaintenance()
                    ? 'The application is currently in maintenance mode.'
                    : 'The application is available.'
            )
        );

        return $checks;
    }

    public function summary(
        Collection $checks
    ): array {
        return [
            'pass' => $checks
                ->where('status', 'pass')
                ->count(),

            'warning' => $checks
                ->where('status', 'warning')
                ->count(),

            'fail' => $checks
                ->where('status', 'fail')
                ->count(),

            'deferred' => $checks
                ->where('status', 'deferred')
                ->count(),

            'info' => $checks
                ->where('status', 'info')
                ->count(),
        ];
    }

    public function runtimeMetrics(): array
    {
        $metrics = [
            'queued_jobs' => null,
            'failed_jobs' => null,
            'database_size' => null,
            'log_size' => 0,
            'free_disk' => null,
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
        } catch (Throwable) {
            //
        }

        $database = config(
            'database.connections.'
            .config('database.default')
            .'.database'
        );

        if (
            DB::connection()->getDriverName()
                === 'sqlite'
            && is_string($database)
            && is_file($database)
        ) {
            $metrics['database_size'] =
                filesize($database) ?: null;
        }

        foreach (
            File::glob(
                storage_path('logs/*.log')
            ) as $log
        ) {
            $metrics['log_size'] +=
                filesize($log) ?: 0;
        }

        $freeDisk = @disk_free_space(
            storage_path()
        );

        if (is_numeric($freeDisk)) {
            $metrics['free_disk'] =
                (int) $freeDisk;
        }

        return $metrics;
    }

    public function latestBackup(): ?array
    {
        $base = $this->backupDirectory();

        if (! is_dir($base)) {
            return null;
        }

        $directories =
            File::directories($base);

        usort(
            $directories,
            fn (
                string $left,
                string $right
            ): int =>
                filemtime($right)
                <=> filemtime($left)
        );

        foreach ($directories as $directory) {
            $manifestPath =
                $directory.'/manifest.json';

            if (! is_file($manifestPath)) {
                continue;
            }

            $manifest = json_decode(
                (string) file_get_contents(
                    $manifestPath
                ),
                true
            );

            if (! is_array($manifest)) {
                continue;
            }

            $verified = true;
            $totalSize = 0;

            foreach (
                $manifest['files'] ?? []
                as $file
            ) {
                $filePath = $directory.'/'
                    .($file['name'] ?? '');

                if (! is_file($filePath)) {
                    $verified = false;
                    continue;
                }

                $actualChecksum = hash_file(
                    'sha256',
                    $filePath
                );

                if (
                    ! hash_equals(
                        (string) (
                            $file['sha256']
                            ?? ''
                        ),
                        $actualChecksum
                    )
                ) {
                    $verified = false;
                }

                $totalSize +=
                    filesize($filePath) ?: 0;
            }

            $createdAt = $manifest[
                'created_at'
            ] ?? date(
                DATE_ATOM,
                filemtime($manifestPath)
            );

            $timestamp = strtotime(
                $createdAt
            ) ?: filemtime($manifestPath);

            return [
                'path' => $directory,
                'name' => basename(
                    $directory
                ),
                'created_at' => $createdAt,
                'timestamp' => $timestamp,
                'age_hours' => max(
                    0,
                    (now()->timestamp - $timestamp)
                    / 3600
                ),
                'driver' => $manifest[
                    'database_driver'
                ] ?? 'unknown',
                'verified' => $verified,
                'total_size' => $totalSize,
                'files' => $manifest[
                    'files'
                ] ?? [],
                'warnings' => $manifest[
                    'warnings'
                ] ?? [],
            ];
        }

        return null;
    }

    public function backupDirectory(): string
    {
        return storage_path(
            'app/private/'
            .config(
                'production-readiness.backups.directory',
                'production-backups'
            )
        );
    }

    public function formatBytes(
        ?int $bytes
    ): string {
        if ($bytes === null) {
            return 'Unavailable';
        }

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $value = max(
            0,
            $bytes
        );

        $unit = 0;

        while (
            $value >= 1024
            && $unit < count($units) - 1
        ) {
            $value /= 1024;
            $unit++;
        }

        return number_format(
            $value,
            $unit === 0 ? 0 : 2
        ).' '.$units[$unit];
    }

    private function migrationStatus(): array
    {
        try {
            if (
                ! Schema::hasTable(
                    'migrations'
                )
            ) {
                return [
                    'status' => 'fail',
                    'details' =>
                        'The migrations table does not exist.',
                ];
            }

            $executed = DB::table(
                'migrations'
            )
                ->pluck('migration')
                ->all();

            $available = collect(
                File::glob(
                    database_path(
                        'migrations/*.php'
                    )
                )
            )
                ->map(
                    fn (string $path): string =>
                        pathinfo(
                            $path,
                            PATHINFO_FILENAME
                        )
                )
                ->all();

            $pending = array_values(
                array_diff(
                    $available,
                    $executed
                )
            );

            return [
                'status' => count($pending) === 0
                    ? 'pass'
                    : 'warning',

                'details' => count($pending) === 0
                    ? 'No pending migrations.'
                    : count($pending)
                        .' migration(s) pending.',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'warning',
                'details' =>
                    'Migration status could not be read: '
                    .$exception->getMessage(),
            ];
        }
    }

    private function heartbeat(
        string $type
    ): array {
        $path = storage_path(
            'app/private/'
            .config(
                'production-readiness.heartbeat.directory',
                'production-readiness'
            )
            .'/'
            .$type
            .'.json'
        );

        if (! is_file($path)) {
            return [
                'status' => 'warning',
                'details' =>
                    ucfirst($type)
                    .' heartbeat has not been recorded yet.',
            ];
        }

        $payload = json_decode(
            (string) file_get_contents(
                $path
            ),
            true
        );

        $timestamp = (int) (
            $payload['timestamp']
            ?? filemtime($path)
        );

        $age = max(
            0,
            now()->timestamp - $timestamp
        );

        $maximumAge = max(
            60,
            (int) config(
                'production-readiness.heartbeat.'
                .$type
                .'_max_age_seconds',
                180
            )
        );

        return [
            'status' => $age <= $maximumAge
                ? 'pass'
                : 'warning',

            'details' => ucfirst($type)
                .' heartbeat was recorded '
                .$age
                .' second(s) ago.',
        ];
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
}
