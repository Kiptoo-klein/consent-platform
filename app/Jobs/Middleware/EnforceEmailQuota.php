<?php

namespace App\Jobs\Middleware;

use App\Models\EmailQuotaAttempt;
use App\Services\EmailQuotaService;
use Closure;
use RuntimeException;
use Throwable;

class EnforceEmailQuota
{
    public function __construct(
        private readonly string $category,
        private readonly ?string $jobKey = null,
        private readonly string $priority =
            EmailQuotaAttempt::PRIORITY_NORMAL
    ) {
    }

    public function handle(
        object $job,
        Closure $next
    ): void {
        if (
            method_exists(
                $job,
                'shouldConsumeEmailQuota'
            )
            && ! $job->shouldConsumeEmailQuota()
        ) {
            $next($job);

            return;
        }

        $quota = app(EmailQuotaService::class);

        $reservation = $quota->reserve(
            category: $this->category,
            jobKey: $this->jobKey,
            priority: $this->priority
        );

        if (! $reservation['allowed']) {
            if (method_exists($job, 'release')) {
                $job->release(
                    $reservation['retry_after']
                );

                return;
            }

            throw new RuntimeException(
                'Email quota is exhausted and the queued job cannot be released.'
            );
        }

        try {
            $next($job);

            $quota->markSent(
                $reservation['attempt_id']
            );
        } catch (Throwable $exception) {
            $quota->markFailed(
                $reservation['attempt_id'],
                $exception
            );

            throw $exception;
        }
    }
}
