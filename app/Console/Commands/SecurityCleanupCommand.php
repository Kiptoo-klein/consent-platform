<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SecurityCleanupCommand extends Command
{
    protected $signature =
        'security:cleanup
        {--dry-run : Show what would be cleaned without changing data}
        {--stale-hours= : Override the stale kiosk-session age}
        {--failed-days= : Override failed-job retention}';

    protected $description =
        'Safely clean stale kiosk sessions, old failed jobs, expired password-reset tokens and database sessions.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option(
            'dry-run'
        );

        $staleHours = max(
            1,
            (int) (
                $this->option(
                    'stale-hours'
                )
                ?: config(
                    'security-hardening.cleanup.stale_kiosk_hours',
                    24
                )
            )
        );

        $failedDays = max(
            1,
            (int) (
                $this->option(
                    'failed-days'
                )
                ?: config(
                    'security-hardening.cleanup.failed_job_days',
                    30
                )
            )
        );

        $sessionDays = max(
            1,
            (int) config(
                'security-hardening.cleanup.database_session_days',
                7
            )
        );

        $results = [];

        $results[] = [
            'Stale kiosk consent records',
            $this->cleanStaleKioskSessions(
                $staleHours,
                $dryRun
            ),
        ];

        $results[] = [
            'Old failed jobs',
            $this->cleanFailedJobs(
                $failedDays,
                $dryRun
            ),
        ];

        $results[] = [
            'Expired password-reset tokens',
            $this->cleanPasswordResetTokens(
                $dryRun
            ),
        ];

        $results[] = [
            'Expired database sessions',
            $this->cleanDatabaseSessions(
                $sessionDays,
                $dryRun
            ),
        ];

        $this->table(
            ['Cleanup item', 'Records'],
            $results
        );

        $this->newLine();

        $this->info(
            $dryRun
                ? 'Dry run completed. No records were changed.'
                : 'Security cleanup completed.'
        );

        return self::SUCCESS;
    }

    private function cleanStaleKioskSessions(
        int $hours,
        bool $dryRun
    ): int {
        if (
            ! Schema::hasTable(
                'consent_sessions'
            )
        ) {
            return 0;
        }

        $query = DB::table(
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
                now()->subHours($hours)
            );

        $count = $query->count();

        if (
            ! $dryRun
            && $count > 0
        ) {
            $query->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $count;
    }

    private function cleanFailedJobs(
        int $days,
        bool $dryRun
    ): int {
        if (
            ! Schema::hasTable(
                'failed_jobs'
            )
        ) {
            return 0;
        }

        $count = DB::table(
            'failed_jobs'
        )
            ->where(
                'failed_at',
                '<',
                now()->subDays($days)
            )
            ->count();

        if (
            ! $dryRun
            && $this->getApplication()
                ?->has(
                    'queue:prune-failed'
                )
        ) {
            try {
                $this->call(
                    'queue:prune-failed',
                    [
                        '--hours' =>
                            $days * 24,
                    ]
                );
            } catch (Throwable $exception) {
                $this->warn(
                    'Failed-job pruning could not run: '
                    .$exception->getMessage()
                );
            }
        }

        return $count;
    }

    private function cleanPasswordResetTokens(
        bool $dryRun
    ): int {
        if (
            ! Schema::hasTable(
                'password_reset_tokens'
            )
        ) {
            return 0;
        }

        $query = DB::table(
            'password_reset_tokens'
        )->where(
            'created_at',
            '<',
            now()->subHours(2)
        );

        $count = $query->count();

        if (
            ! $dryRun
            && $count > 0
        ) {
            $query->delete();
        }

        return $count;
    }

    private function cleanDatabaseSessions(
        int $days,
        bool $dryRun
    ): int {
        if (
            ! Schema::hasTable('sessions')
            || ! Schema::hasColumn(
                'sessions',
                'last_activity'
            )
        ) {
            return 0;
        }

        $query = DB::table(
            'sessions'
        )->where(
            'last_activity',
            '<',
            now()
                ->subDays($days)
                ->timestamp
        );

        $count = $query->count();

        if (
            ! $dryRun
            && $count > 0
        ) {
            $query->delete();
        }

        return $count;
    }
}
