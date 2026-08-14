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
                /*
                 * Laravel Cloud managed queues reject per-message delays
                 * longer than 15 minutes. Re-check quota periodically until
                 * the original rolling-window capacity becomes available.
                 */
                $job->release(
                    min(
                        max(
                            1,
                            (int) $reservation[
                                'retry_after'
                            ]
                        ),
                        900
                    )
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

            if (
                method_exists(
                    $job,
                    'failImmediatelyOnEmailDeliveryError'
                )
                && $job
                    ->failImmediatelyOnEmailDeliveryError()
                && method_exists($job, 'fail')
            ) {
                $job->fail($exception);

                return;
            }

            throw $exception;
        }
    }
}
