<?php

namespace App\Console\Commands;

use App\Services\ProductionReadinessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProductionPruneCommand extends Command
{
    protected $signature =
        'production:prune
        {--dry-run : Show removals without deleting files or records}';

    protected $description =
        'Apply backup, log and failed-job retention policies.';

    public function handle(
        ProductionReadinessService $service
    ): int {
        $dryRun = (bool) $this->option(
            'dry-run'
        );

        $removedBackups =
            $this->pruneBackups(
                $service,
                $dryRun
            );

        $removedLogs =
            $this->pruneLogs(
                $dryRun
            );

        $removedFailedJobs =
            $this->pruneFailedJobs(
                $dryRun
            );

        $this->table(
            [
                'Retention item',
                'Records removed',
            ],
            [
                [
                    'Backup sets',
                    $removedBackups,
                ],
                [
                    'Rotated logs',
                    $removedLogs,
                ],
                [
                    'Old failed jobs',
                    $removedFailedJobs,
                ],
            ]
        );

        $this->components->info(
            $dryRun
                ? 'Dry run complete. Nothing was deleted.'
                : 'Retention cleanup completed.'
        );

        return self::SUCCESS;
    }

    private function pruneBackups(
        ProductionReadinessService $service,
        bool $dryRun
    ): int {
        $base = $service
            ->backupDirectory();

        if (! is_dir($base)) {
            return 0;
        }

        $retentionDays = max(
            1,
            (int) config(
                'production-readiness.backups.retention_days',
                14
            )
        );

        $retentionCount = max(
            1,
            (int) config(
                'production-readiness.backups.retention_count',
                14
            )
        );

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

        $cutoff = now()
            ->subDays($retentionDays)
            ->timestamp;

        $removed = 0;

        foreach (
            $directories as $index => $directory
        ) {
            $tooMany =
                $index >= $retentionCount;

            $tooOld =
                filemtime($directory)
                < $cutoff;

            if (! $tooMany && ! $tooOld) {
                continue;
            }

            $removed++;

            if (! $dryRun) {
                File::deleteDirectory(
                    $directory
                );
            }
        }

        return $removed;
    }

    private function pruneLogs(
        bool $dryRun
    ): int {
        $retentionDays = max(
            1,
            (int) config(
                'production-readiness.cleanup.log_retention_days',
                30
            )
        );

        $cutoff = now()
            ->subDays($retentionDays)
            ->timestamp;

        $removed = 0;

        foreach (
            File::glob(
                storage_path('logs/*.log')
            ) as $path
        ) {
            if (
                basename($path)
                === 'laravel.log'
            ) {
                continue;
            }

            if (filemtime($path) >= $cutoff) {
                continue;
            }

            $removed++;

            if (! $dryRun) {
                File::delete($path);
            }
        }

        return $removed;
    }

    private function pruneFailedJobs(
        bool $dryRun
    ): int {
        if (
            ! Schema::hasTable(
                'failed_jobs'
            )
        ) {
            return 0;
        }

        $retentionDays = max(
            1,
            (int) config(
                'production-readiness.cleanup.failed_job_retention_days',
                30
            )
        );

        try {
            $query = DB::table(
                'failed_jobs'
            )->where(
                'failed_at',
                '<',
                now()->subDays(
                    $retentionDays
                )
            );

            $count = $query->count();

            if (
                ! $dryRun
                && $count > 0
            ) {
                $query->delete();
            }

            return $count;
        } catch (Throwable) {
            return 0;
        }
    }
}
