<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionInvoiceStatus;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceNotification;
use App\Services\SubscriptionInvoiceNotificationService;
use Illuminate\Console\Command;

class SendSubscriptionInvoiceReminders extends Command
{
    protected $signature =
        'subscription-invoices:send-reminders '
        .'{--dry-run : Show due reminders without sending email} '
        .'{--chunk= : Number of invoices to process per batch}';

    protected $description =
        'Send scheduled reminders for upcoming and overdue subscription invoices.';

    public function handle(
        SubscriptionInvoiceNotificationService $notificationService
    ): int {
        $beforeDueDays =
            $notificationService
                ->configuredBeforeDueDays();

        $overdueDays =
            $notificationService
                ->configuredOverdueDays();

        if (
            $beforeDueDays === []
            && $overdueDays === []
        ) {
            $this->warn(
                'No invoice reminder milestones are configured.'
            );

            return self::SUCCESS;
        }

        $configuredChunkSize = max(
            1,
            (int) config(
                'subscription-invoice-notifications.chunk_size',
                100
            )
        );

        $requestedChunkSize =
            $this->option('chunk');

        $chunkSize = $requestedChunkSize === null
            ? $configuredChunkSize
            : max(
                1,
                min(
                    (int) $requestedChunkSize,
                    1000
                )
            );

        $maximumBeforeDueDays =
            $beforeDueDays === []
                ? 0
                : max($beforeDueDays);

        $maximumOverdueDays =
            $overdueDays === []
                ? 0
                : max($overdueDays);

        $firstDueDate =
            today()
                ->subDays(
                    $maximumOverdueDays
                )
                ->toDateString();

        $lastDueDate =
            today()
                ->addDays(
                    $maximumBeforeDueDays
                )
                ->toDateString();

        $sent = 0;
        $failed = 0;
        $skipped = 0;

        SubscriptionInvoice::query()
            ->with([
                'organization',
                'plan',
                'subscription.billingOwner',
            ])
            ->whereIn(
                'status',
                [
                    SubscriptionInvoiceStatus::ISSUED->value,
                    SubscriptionInvoiceStatus::OVERDUE->value,
                ]
            )
            ->whereNotNull('due_date')
            ->whereDate(
                'due_date',
                '>=',
                $firstDueDate
            )
            ->whereDate(
                'due_date',
                '<=',
                $lastDueDate
            )
            ->orderBy('id')
            ->chunkById(
                $chunkSize,
                function ($invoices) use (
                    $notificationService,
                    &$sent,
                    &$failed,
                    &$skipped
                ): void {
                    foreach (
                        $invoices
                        as $invoice
                    ) {
                        $reminderKey =
                            $notificationService
                                ->reminderKeyFor(
                                    $invoice
                                );

                        if ($reminderKey === null) {
                            $skipped++;

                            continue;
                        }

                        $recipient =
                            $notificationService
                                ->eligibleRecipient(
                                    $invoice
                                );

                        if ($recipient === null) {
                            $skipped++;

                            continue;
                        }

                        if (
                            $notificationService
                                ->automaticReminderAlreadySent(
                                    $invoice,
                                    $reminderKey
                                )
                            || $notificationService
                                ->recentAttemptExists(
                                    $invoice,
                                    $reminderKey
                                )
                        ) {
                            $skipped++;

                            continue;
                        }

                        if ($this->option('dry-run')) {
                            $this->line(
                                "Due: invoice #{$invoice->id} - {$reminderKey} reminder to {$recipient->email}"
                            );

                            $skipped++;

                            continue;
                        }

                        $notification =
                            $notificationService
                                ->sendAutomaticReminder(
                                    invoice:
                                        $invoice,

                                    reminderKey:
                                        $reminderKey
                                );

                        if ($notification === null) {
                            $skipped++;

                            continue;
                        }

                        if (
                            $notification->status
                                === SubscriptionInvoiceNotification::STATUS_SENT
                        ) {
                            $sent++;
                        } else {
                            $failed++;
                        }
                    }
                }
            );

        $this->info(
            "Invoice reminder run complete. Sent: {$sent}; failed: {$failed}; skipped: {$skipped}."
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
