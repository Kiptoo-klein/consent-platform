<?php

namespace Tests\Feature;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Jobs\SendCompletedConsentMailJob;
use App\Mail\ConsentCompletedMail;
use App\Models\ConsentSession;
use App\Services\EmailQuotaService;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CompletedConsentMailQueueSafetyTest extends TestCase
{
    public function test_completed_mail_rejects_null_pdf_data_before_symfony_mime(): void
    {
        config()->set(
            'consent-pdf.disk',
            'completed-mail-test'
        );

        $disk = Mockery::mock();

        $disk
            ->shouldReceive('get')
            ->once()
            ->with('consent-records/test.pdf')
            ->andReturn(null);

        Storage::shouldReceive('disk')
            ->once()
            ->with('completed-mail-test')
            ->andReturn($disk);

        $session = new ConsentSession();
        $session->pdf_path =
            'consent-records/test.pdf';

        $mail = new ConsentCompletedMail(
            $session
        );

        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'The completed consent PDF is missing or invalid for email delivery.'
        );

        $mail->attachments();
    }

    public function test_completed_mail_job_opts_into_immediate_delivery_failure(): void
    {
        $job =
            new SendCompletedConsentMailJob(123);

        $this->assertTrue(
            $job
                ->failImmediatelyOnEmailDeliveryError()
        );
    }

    public function test_email_quota_middleware_can_fail_delivery_without_retry_storm(): void
    {
        $quota =
            Mockery::mock(
                EmailQuotaService::class
            );

        $quota
            ->shouldReceive('reserve')
            ->once()
            ->andReturn([
                'allowed' => true,
                'attempt_id' => 77,
            ]);

        $quota
            ->shouldReceive('markFailed')
            ->once()
            ->with(
                77,
                Mockery::type(
                    RuntimeException::class
                )
            );

        $quota
            ->shouldNotReceive('markSent');

        app()->instance(
            EmailQuotaService::class,
            $quota
        );

        $job =
            new class
            {
                public bool $failed = false;

                public function failImmediatelyOnEmailDeliveryError(): bool
                {
                    return true;
                }

                public function fail(
                    mixed $exception = null
                ): void {
                    $this->failed = true;
                }
            };

        $middleware =
            new EnforceEmailQuota(
                category: 'test',
                jobKey: 'test:77'
            );

        $middleware->handle(
            $job,
            function (): void {
                throw new RuntimeException(
                    'Permanent delivery failure.'
                );
            }
        );

        $this->assertTrue(
            $job->failed
        );
    }
}
