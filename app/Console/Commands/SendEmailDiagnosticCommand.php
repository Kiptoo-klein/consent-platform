<?php

namespace App\Console\Commands;

use App\Mail\EmailConfigurationTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailDiagnosticCommand extends Command
{
    protected $signature =
        'mail:diagnose
        {recipient : Email address that should receive the test}
        {--queue : Send using the configured queue}
        {--no-attachment : Do not attach the diagnostic text file}';

    protected $description =
        'Send an email configuration test without exposing SMTP credentials.';

    public function handle(): int
    {
        $recipient = (string) $this->argument(
            'recipient'
        );

        if (
            ! filter_var(
                $recipient,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $this->error(
                'Enter a valid recipient email address.'
            );

            return self::FAILURE;
        }

        $mailer = (string) config(
            'mail.default',
            'log'
        );

        $deliveryMode = $this->option('queue')
            ? 'queued'
            : 'immediate';

        $mailable =
            new EmailConfigurationTestMail(
                requestedBy:
                    'Artisan command',

                deliveryMode:
                    $deliveryMode,

                mailerName:
                    $mailer,

                includeAttachment:
                    ! $this->option(
                        'no-attachment'
                    ),
            );

        try {
            $pendingMail = Mail::mailer($mailer)
                ->to($recipient);

            if (
                $this->option('queue')
                || app(
                    \App\Services\EmailQuotaService::class
                )->shouldQueue()
            ) {
                $pendingMail->queue($mailable);
                $this->info(
                    "Test email queued using {$mailer}."
                );
            } else {
                $pendingMail->send($mailable);
                $this->info(
                    "Test email sent using {$mailer}."
                );
            }

            if (
                in_array(
                    $mailer,
                    ['log', 'array'],
                    true
                )
            ) {
                $this->warn(
                    "The {$mailer} mailer does not deliver to an inbox."
                );
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                'Email test failed: '
                .$exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}
