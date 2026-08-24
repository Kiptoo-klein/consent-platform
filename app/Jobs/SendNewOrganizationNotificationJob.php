<?php

namespace App\Jobs;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Mail\NewOrganizationNotificationMail;
use App\Models\Organization;
use App\Models\User;
use App\Models\EmailQuotaAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNewOrganizationNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1000;

    public int $maxExceptions = 3;

    public int $timeout = 120;

    public function __construct(
        public int $organizationId
    ) {
    }

    public function middleware(): array
    {
        return [
            new EnforceEmailQuota(
                category: 'new_organization_notification',
                jobKey:
                    'new_organization_notification:'
                    .$this->organizationId,
                priority:
                    EmailQuotaAttempt::PRIORITY_CRITICAL
            ),
        ];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addDays(40);
    }

    public function handle(): void
    {
        $organization = Organization::query()
            ->findOrFail($this->organizationId);

        $recipients = User::query()
            ->whereNull('organization_id')
            ->where('is_active', true)
            ->whereHas(
                'platformRole',
                function ($query): void {
                    $query->where(
                        'slug',
                        'super-admin'
                    );
                }
            )
            ->get();

        $seen = [];

        foreach ($recipients as $recipient) {
            $email = mb_strtolower(
                trim((string) $recipient->email)
            );

            if (
                ! filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
                || isset($seen[$email])
            ) {
                continue;
            }

            $seen[$email] = true;

            Mail::to($email)->send(
                new NewOrganizationNotificationMail(
                    $organization
                )
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
