<?php

namespace App\Console\Commands;

use App\Models\ConsentNotification;
use App\Models\ConsentSession;
use App\Services\ConsentNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendConsentReminders extends Command
{
    protected $signature =
        'consent:send-reminders {--dry-run : Show due reminders without sending email}';

    protected $description =
        'Send scheduled email reminders for individual consent records approaching their deadline.';

    public function handle(
        ConsentNotificationService $notificationService
    ): int {
        $reminderDays = collect(
            config(
                'consent-notifications.reminder_days',
                [3, 1]
            )
        )
            ->map(fn ($days): int => (int) $days)
            ->filter(fn (int $days): bool => $days > 0)
            ->unique()
            ->sortDesc()
            ->values();

        if ($reminderDays->isEmpty()) {
            $this->warn(
                'No reminder days are configured.'
            );

            return self::SUCCESS;
        }

        $now = CarbonImmutable::now();
        $maxDays = (int) $reminderDays->max();
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        ConsentSession::query()
            ->whereNull('signing_station_id')
            ->whereIn('status', [
                ConsentSession::STATUS_PENDING,
                ConsentSession::STATUS_IN_PROGRESS,
            ])
            ->whereNotNull('signer_email')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->where(
                'expires_at',
                '<=',
                $now->addDays($maxDays)
            )
            ->orderBy('id')
            ->chunkById(
                100,
                function ($sessions) use (
                    $notificationService,
                    $reminderDays,
                    $now,
                    &$sent,
                    &$failed,
                    &$skipped
                ): void {
                    foreach ($sessions as $consentSession) {
                        $expiresAt = CarbonImmutable::parse(
                            $consentSession->expires_at
                        );

                        $remainingSeconds =
                            $expiresAt->getTimestamp()
                            - $now->getTimestamp();

                        if ($remainingSeconds <= 0) {
                            $skipped++;
                            continue;
                        }

                        $daysRemaining = (int) ceil(
                            $remainingSeconds / 86400
                        );

                        if (! $reminderDays->contains(
                            $daysRemaining
                        )) {
                            $skipped++;
                            continue;
                        }

                        if (
                            $notificationService
                                ->automaticReminderAlreadySent(
                                    $consentSession,
                                    $daysRemaining
                                )
                        ) {
                            $skipped++;
                            continue;
                        }

                        if ($this->option('dry-run')) {
                            $this->line(
                                "Due: record #{$consentSession->id} — {$daysRemaining} day reminder to {$consentSession->signer_email}"
                            );
                            $skipped++;
                            continue;
                        }

                        $notification =
                            $notificationService
                                ->sendAutomaticReminder(
                                    $consentSession,
                                    $daysRemaining
                                );

                        if ($notification === null) {
                            $skipped++;
                            continue;
                        }

                        if (
                            $notification->status
                            === ConsentNotification::STATUS_SENT
                        ) {
                            $sent++;
                            $this->info(
                                "Sent record #{$consentSession->id} to {$notification->recipient_email}."
                            );
                        } else {
                            $failed++;
                            $this->error(
                                "Failed record #{$consentSession->id}: {$notification->error_message}"
                            );
                        }
                    }
                }
            );

        $this->newLine();
        $this->info(
            "Reminder run complete. Sent: {$sent}; failed: {$failed}; skipped: {$skipped}."
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
