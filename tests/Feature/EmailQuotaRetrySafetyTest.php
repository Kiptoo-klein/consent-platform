<?php

namespace Tests\Feature;

use App\Jobs\RetrySignedConsentPdfJob;
use App\Jobs\SendCompletedConsentMailJob;
use App\Jobs\SendConsentNotificationJob;
use App\Jobs\SendSignedConsentPdfJob;
use App\Jobs\SendSubscriptionInvoiceNotificationJob;
use App\Jobs\SendSubscriptionWorkflowNotificationJob;
use App\Mail\EmailConfigurationTestMail;
use App\Notifications\QuotaResetPassword;
use App\Notifications\QuotaVerifyEmail;
use Tests\TestCase;

class EmailQuotaRetrySafetyTest extends TestCase
{
    public function test_quota_jobs_keep_large_release_budget_but_limit_real_exceptions(): void
    {
        $jobs = [
            new RetrySignedConsentPdfJob(1),

            new SendCompletedConsentMailJob(1),

            new SendConsentNotificationJob(1),

            new SendSignedConsentPdfJob(1),

            new SendSubscriptionInvoiceNotificationJob(1),

            new SendSubscriptionWorkflowNotificationJob(1),

            new EmailConfigurationTestMail(
                requestedBy: 'test@example.com',
                deliveryMode: 'test',
                mailerName: 'array',
            ),

            new QuotaResetPassword('token'),

            new QuotaVerifyEmail(),
        ];

        foreach ($jobs as $job) {
            $this->assertSame(
                1000,
                $job->tries,
                get_class($job)
            );

            $this->assertSame(
                3,
                $job->maxExceptions,
                get_class($job)
            );
        }
    }
}
