<?php

namespace App\Console\Commands;

use App\Services\ProductionReadinessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class ProductionBackupCommand extends Command
{
    protected $signature =
        'production:backup
        {--database-only : Skip private application files}
        {--prune : Apply backup retention after completion}';

    protected $description =
        'Create a verified database and private-file backup.';

    public function handle(
        ProductionReadinessService $service
    ): int {
        $base = $service->backupDirectory();

        File::ensureDirectoryExists(
            $base,
            0700,
            true
        );

        $identifier = 'backup-'
            .now()->format('Ymd_His')
            .'-'
            .substr(
                (string) Str::uuid(),
                0,
                8
            );

        $directory = $base.'/'.$identifier;

        File::ensureDirectoryExists(
            $directory,
            0700,
            true
        );

        $warnings = [];
        $files = [];

        try {
            $driver = DB::connection()
                ->getDriverName();

            $databaseFile =
                $this->backupDatabase(
                    $driver,
                    $directory
                );

            $files[] = $this->fileRecord(
                $databaseFile
            );

            $includePrivateFiles =
                ! $this->option(
                    'database-only'
                )
                && (bool) config(
                    'production-readiness.backups.include_private_files',
                    true
                );

            if ($includePrivateFiles) {
                $privateArchive =
                    $this->backupPrivateFiles(
                        $directory,
                        $base
                    );

                if ($privateArchive !== null) {
                    $files[] =
                        $this->fileRecord(
                            $privateArchive
                        );
                } else {
                    $warnings[] =
                        'Private files were skipped because the tar utility is unavailable.';
                }
            }

            $manifest = [
                'version' => 1,
                'created_at' =>
                    now()->toIso8601String(),

                'application' =>
                    config('app.name'),

                'environment' =>
                    app()->environment(),

                'database_driver' =>
                    $driver,

                'hostname' =>
                    gethostname() ?: null,

                'files' => $files,
                'warnings' => $warnings,
            ];

            $manifestPath =
                $directory.'/manifest.json';

            File::put(
                $manifestPath,
                json_encode(
                    $manifest,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                )
            );

            @chmod(
                $manifestPath,
                0600
            );

            if ($this->option('prune')) {
                Artisan::call(
                    'production:prune'
                );
            }

            if (! $this->output->isQuiet()) {
                $this->components->info(
                    'Backup created successfully.'
                );

                $this->line(
                    'Location: '.$directory
                );

                foreach ($files as $file) {
                    $this->line(
                        ' - '
                        .$file['name']
                        .' ('
                        .$service->formatBytes(
                            $file['size']
                        )
                        .')'
                    );
                }

                foreach ($warnings as $warning) {
                    $this->components->warn(
                        $warning
                    );
                }
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            File::deleteDirectory(
                $directory
            );

            $this->components->error(
                'Backup failed: '
                .$exception->getMessage()
            );

            return self::FAILURE;
        }
    }

    private function backupDatabase(
        string $driver,
        string $directory
    ): string {
        return match ($driver) {
            'sqlite' =>
                $this->backupSqlite(
                    $directory
                ),

            'mysql', 'mariadb' =>
                $this->backupMysql(
                    $directory
                ),

            'pgsql' =>
                $this->backupPostgres(
                    $directory
                ),

            default => throw new RuntimeException(
                'Automatic backup is not configured for database driver: '
                .$driver
            ),
        };
    }

    private function backupSqlite(
        string $directory
    ): string {
        $database = config(
            'database.connections.'
            .config('database.default')
            .'.database'
        );

        if (
            ! is_string($database)
            || $database === ''
            || $database === ':memory:'
        ) {
            throw new RuntimeException(
                'The SQLite database path is invalid.'
            );
        }

        if (! str_starts_with($database, '/')) {
            $database = base_path(
                $database
            );
        }

        if (! is_file($database)) {
            throw new RuntimeException(
                'SQLite database file was not found: '
                .$database
            );
        }

        $target =
            $directory.'/database.sqlite';

        try {
            $quoted = DB::connection()
                ->getPdo()
                ->quote($target);

            DB::connection()
                ->getPdo()
                ->exec(
                    'VACUUM INTO '.$quoted
                );
        } catch (Throwable) {
            try {
                DB::statement(
                    'PRAGMA wal_checkpoint(FULL)'
                );
            } catch (Throwable) {
                //
            }

            if (! copy($database, $target)) {
                throw new RuntimeException(
                    'SQLite database copy failed.'
                );
            }
        }

        @chmod($target, 0600);

        return $target;
    }

    private function backupMysql(
        string $directory
    ): string {
        $binary = (
            new ExecutableFinder()
        )->find('mysqldump');

        if ($binary === null) {
            throw new RuntimeException(
                'mysqldump is not installed.'
            );
        }

        $connection = config(
            'database.connections.'
            .config('database.default')
        );

        $target =
            $directory.'/database.sql';

        $arguments = [
            $binary,
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--host='
                .($connection['host']
                    ?? '127.0.0.1'),

            '--port='
                .($connection['port']
                    ?? 3306),

            '--user='
                .($connection['username']
                    ?? ''),

            (string) (
                $connection['database']
                ?? ''
            ),
        ];

        $this->runDumpProcess(
            $arguments,
            $target,
            [
                'MYSQL_PWD' =>
                    (string) (
                        $connection['password']
                        ?? ''
                    ),
            ]
        );

        return $target;
    }

    private function backupPostgres(
        string $directory
    ): string {
        $binary = (
            new ExecutableFinder()
        )->find('pg_dump');

        if ($binary === null) {
            throw new RuntimeException(
                'pg_dump is not installed.'
            );
        }

        $connection = config(
            'database.connections.'
            .config('database.default')
        );

        $target =
            $directory.'/database.sql';

        $arguments = [
            $binary,
            '--format=plain',
            '--no-owner',
            '--no-privileges',
            '--host='
                .($connection['host']
                    ?? '127.0.0.1'),

            '--port='
                .($connection['port']
                    ?? 5432),

            '--username='
                .($connection['username']
                    ?? ''),

            '--dbname='
                .($connection['database']
                    ?? ''),
        ];

        $this->runDumpProcess(
            $arguments,
            $target,
            [
                'PGPASSWORD' =>
                    (string) (
                        $connection['password']
                        ?? ''
                    ),
            ]
        );

        return $target;
    }

    private function runDumpProcess(
        array $arguments,
        string $target,
        array $environment
    ): void {
        $handle = fopen(
            $target,
            'wb'
        );

        if ($handle === false) {
            throw new RuntimeException(
                'Could not create database dump file.'
            );
        }

        $stderr = '';

        $process = new Process(
            $arguments,
            base_path(),
            $environment
        );

        $process->setTimeout(600);

        try {
            $process->run(
                function (
                    string $type,
                    string $buffer
                ) use (
                    $handle,
                    &$stderr
                ): void {
                    if (
                        $type === Process::OUT
                    ) {
                        fwrite(
                            $handle,
                            $buffer
                        );
                    } else {
                        $stderr .= $buffer;
                    }
                }
            );
        } finally {
            fclose($handle);
        }

        if (! $process->isSuccessful()) {
            @unlink($target);

            throw new RuntimeException(
                trim($stderr)
                ?: 'Database dump command failed.'
            );
        }

        if (
            ! is_file($target)
            || filesize($target) < 1
        ) {
            throw new RuntimeException(
                'Database dump is empty.'
            );
        }

        @chmod($target, 0600);
    }

    private function backupPrivateFiles(
        string $directory,
        string $backupBase
    ): ?string {
        $privateRoot = storage_path(
            'app/private'
        );

        if (! is_dir($privateRoot)) {
            return null;
        }

        $binary = (
            new ExecutableFinder()
        )->find('tar');

        if ($binary === null) {
            return null;
        }

        $target =
            $directory
            .'/private-files.tar.gz';

        $backupDirectoryName =
            basename($backupBase);

        $process = new Process(
            [
                $binary,
                '-czf',
                $target,
                '-C',
                $privateRoot,
                '--exclude=./'
                    .$backupDirectoryName,

                '--exclude='
                    .$backupDirectoryName,

                '.',
            ],
            base_path()
        );

        $process->setTimeout(900);
        $process->mustRun();

        if (
            ! is_file($target)
            || filesize($target) < 1
        ) {
            throw new RuntimeException(
                'Private-file archive is empty.'
            );
        }

        @chmod($target, 0600);

        return $target;
    }

    private function fileRecord(
        string $path
    ): array {
        return [
            'name' => basename($path),
            'size' =>
                filesize($path) ?: 0,

            'sha256' =>
                hash_file(
                    'sha256',
                    $path
                ),
        ];
    }
}
