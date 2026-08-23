<?php

namespace Tests\Feature;

use App\Jobs\RetrySignedConsentPdfJob;
use App\Jobs\SendCompletedConsentMailJob;
use App\Jobs\SendConsentNotificationJob;
use App\Jobs\SendSignedConsentPdfJob;
use App\Jobs\SendSubscriptionInvoiceNotificationJob;
use App\Jobs\SendSubscriptionWorkflowNotificationJob;
use App\Mail\EmailConfigurationTestMail;
use App\Notifications\AccountRestorationRequested;
use App\Notifications\OrganizationUserInvitation;
use App\Notifications\OrganizationWelcome;
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

            new OrganizationWelcome(
                'Example Organization'
            ),

            new OrganizationUserInvitation(
                token: 'example-token',
                organizationName: 'Example Organization',
                roleName: 'Staff'
            ),

            new AccountRestorationRequested(
                requestType: 'user',
                requesterName: 'Example User',
                requesterEmail: 'user@example.com',
                organizationName: 'Example Organization',
                reviewUrl: 'https://example.com/review',
                requestedAt: 'Aug 23, 2026 19:43 EAT'
            ),
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
