<?php

namespace App\Console\Commands;

use App\Services\ProductionReadinessService;
use Illuminate\Console\Command;

class ProductionCheckCommand extends Command
{
    protected $signature =
        'production:check
        {--json : Return machine-readable JSON}
        {--fail-on=critical : critical, warning, or never}';

    protected $description =
        'Validate application production readiness.';

    public function handle(
        ProductionReadinessService $service
    ): int {
        $checks = $service->checks();
        $summary = $service->summary(
            $checks
        );

        if ($this->option('json')) {
            $this->line(
                json_encode(
                    [
                        'summary' => $summary,
                        'checks' =>
                            $checks->values(),
                    ],
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                )
            );
        } else {
            $this->table(
                [
                    'Category',
                    'Check',
                    'Status',
                    'Details',
                ],
                $checks
                    ->map(
                        fn (array $check): array =>
                            [
                                $check['category'],
                                $check['label'],
                                strtoupper(
                                    $check['status']
                                ),
                                $check['details'],
                            ]
                    )
                    ->all()
            );

            $this->newLine();

            $this->components->info(
                sprintf(
                    'Pass: %d | Warning: %d | Fail: %d | Deferred: %d',
                    $summary['pass'],
                    $summary['warning'],
                    $summary['fail'],
                    $summary['deferred']
                )
            );
        }

        $failOn = strtolower(
            (string) $this->option(
                'fail-on'
            )
        );

        if ($failOn === 'never') {
            return self::SUCCESS;
        }

        if (
            $summary['fail'] > 0
            || (
                $failOn === 'warning'
                && $summary['warning'] > 0
            )
        ) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
