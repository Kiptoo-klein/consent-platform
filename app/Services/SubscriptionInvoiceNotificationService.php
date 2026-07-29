<?php

namespace App\Services;

use App\Enums\SubscriptionInvoiceStatus;
use App\Mail\SubscriptionInvoiceReminderMail;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceNotification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SubscriptionInvoiceNotificationService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    /**
     * @return list<int>
     */
    public function configuredBeforeDueDays(): array
    {
        return collect(
            config(
                'subscription-invoice-notifications.before_due_days',
                [3, 1]
            )
        )
            ->map(
                fn ($days): int =>
                    (int) $days
            )
            ->filter(
                fn (int $days): bool =>
                    $days > 0
            )
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    public function configuredOverdueDays(): array
    {
        return collect(
            config(
                'subscription-invoice-notifications.overdue_days',
                [1, 7]
            )
        )
            ->map(
                fn ($days): int =>
                    (int) $days
            )
            ->filter(
                fn (int $days): bool =>
                    $days > 0
            )
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    public function reminderKeyFor(
        SubscriptionInvoice $invoice
    ): ?string {
        if ($invoice->due_date === null) {
            return null;
        }

        $timezone = (string) config(
            'app.timezone',
            'UTC'
        );

        $today = CarbonImmutable::today(
            $timezone
        );

        $dueDate = CarbonImmutable::parse(
            $invoice->due_date->toDateString(),
            $timezone
        )->startOfDay();

        if (
            $invoice->status
                === SubscriptionInvoiceStatus::ISSUED
            && $dueDate->isAfter($today)
        ) {
            $daysUntilDue = (int) $today
                ->diffInDays($dueDate);

            if (
                in_array(
                    $daysUntilDue,
                    $this->configuredBeforeDueDays(),
                    true
                )
            ) {
                return $daysUntilDue === 1
                    ? SubscriptionInvoiceNotification::REMINDER_DUE_IN_1_DAY
                    : "due_in_{$daysUntilDue}_days";
            }
        }

        if (
            $invoice->status
                === SubscriptionInvoiceStatus::OVERDUE
            && $dueDate->isBefore($today)
        ) {
            $daysOverdue = (int) $dueDate
                ->diffInDays($today);

            if (
                in_array(
                    $daysOverdue,
                    $this->configuredOverdueDays(),
                    true
                )
            ) {
                return $daysOverdue === 1
                    ? SubscriptionInvoiceNotification::REMINDER_OVERDUE_1_DAY
                    : "overdue_{$daysOverdue}_days";
            }
        }

        return null;
    }

    public function eligibleRecipient(
        SubscriptionInvoice $invoice
    ): ?User {
        $invoice->loadMissing([
            'subscription.billingOwner',
        ]);

        $recipient =
            $invoice
                ->subscription
                ?->billingOwner;

        if (
            $recipient === null
            || $recipient->is_active !== true
            || (int) $recipient->organization_id
                !== (int) $invoice->organization_id
        ) {
            return null;
        }

        $email = trim(
            (string) $recipient->email
        );

        if (
            ! filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return null;
        }

        return $recipient;
    }

    public function automaticReminderAlreadySent(
        SubscriptionInvoice $invoice,
        string $reminderKey
    ): bool {
        return SubscriptionInvoiceNotification::query()
            ->where(
                'subscription_invoice_id',
                $invoice->id
            )
            ->where(
                'reminder_key',
                $reminderKey
            )
            ->where(
                'status',
                SubscriptionInvoiceNotification::STATUS_SENT
            )
            ->exists();
    }

    public function recentAttemptExists(
        SubscriptionInvoice $invoice,
        string $reminderKey
    ): bool {
        $retryMinutes = max(
            1,
            (int) config(
                'subscription-invoice-notifications.automatic_retry_minutes',
                60
            )
        );

        return SubscriptionInvoiceNotification::query()
            ->where(
                'subscription_invoice_id',
                $invoice->id
            )
            ->where(
                'reminder_key',
                $reminderKey
            )
            ->where(
                'created_at',
                '>=',
                now()->subMinutes(
                    $retryMinutes
                )
            )
            ->exists();
    }

    public function sendAutomaticReminder(
        SubscriptionInvoice $invoice,
        string $reminderKey
    ): ?SubscriptionInvoiceNotification {
        $notification = DB::transaction(
            function () use (
                $invoice,
                $reminderKey
            ): ?SubscriptionInvoiceNotification {
                $lockedInvoice =
                    SubscriptionInvoice::query()
                        ->whereKey(
                            $invoice->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $lockedInvoice->load([
                    'organization',
                    'plan',
                    'subscription.billingOwner',
                ]);

                if (
                    $this->reminderKeyFor(
                        $lockedInvoice
                    ) !== $reminderKey
                ) {
                    return null;
                }

                $recipient =
                    $this->eligibleRecipient(
                        $lockedInvoice
                    );

                if ($recipient === null) {
                    return null;
                }

                if (
                    $this->automaticReminderAlreadySent(
                        $lockedInvoice,
                        $reminderKey
                    )
                    || $this->recentAttemptExists(
                        $lockedInvoice,
                        $reminderKey
                    )
                ) {
                    return null;
                }

                $mailable =
                    new SubscriptionInvoiceReminderMail(
                        invoice:
                            $lockedInvoice,

                        reminderKey:
                            $reminderKey
                    );

                return SubscriptionInvoiceNotification::query()
                    ->create([
                        'organization_id' =>
                            $lockedInvoice->organization_id,

                        'subscription_invoice_id' =>
                            $lockedInvoice->id,

                        'recipient_user_id' =>
                            $recipient->id,

                        'reminder_key' =>
                            $reminderKey,

                        'status' =>
                            SubscriptionInvoiceNotification::STATUS_PROCESSING,

                        'recipient_email' =>
                            trim(
                                (string) $recipient->email
                            ),

                        'subject' =>
                            $mailable->subjectLine(),

                        'message' =>
                            $mailable->introMessage(),

                        'scheduled_for' =>
                            now(),

                        'sent_at' => null,
                        'failed_at' => null,
                        'error_message' => null,

                        'metadata' => [
                            'invoice_number' =>
                                $lockedInvoice->invoice_number,

                            'due_date' =>
                                $lockedInvoice
                                    ->due_date
                                    ?->toDateString(),

                            'total_amount' =>
                                $lockedInvoice->total_amount,

                            'currency' =>
                                $lockedInvoice->currency,

                            'plan_name' =>
                                $lockedInvoice
                                    ->plan
                                    ?->name,
                        ],
                    ]);
            },
            3
        );

        if ($notification === null) {
            return null;
        }

        $invoiceForMail =
            SubscriptionInvoice::query()
                ->with([
                    'organization',
                    'plan',
                    'subscription.billingOwner',
                ])
                ->findOrFail(
                    $invoice->id
                );

        try {
            Mail::to(
                $notification->recipient_email
            )->send(
                new SubscriptionInvoiceReminderMail(
                    invoice:
                        $invoiceForMail,

                    reminderKey:
                        $reminderKey
                )
            );

            $notification->forceFill([
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_SENT,

                'sent_at' =>
                    now(),

                'failed_at' => null,
                'error_message' => null,
            ])->save();
        } catch (Throwable $exception) {
            report($exception);

            $notification->forceFill([
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,

                'sent_at' => null,

                'failed_at' =>
                    now(),

                'error_message' =>
                    mb_substr(
                        $exception->getMessage(),
                        0,
                        2000
                    ),
            ])->save();
        }

        return $notification->refresh();
    }

    public function retryFailedReminder(
        SubscriptionInvoice $invoice,
        SubscriptionInvoiceNotification $failedNotification,
        int $requestedByUserId
    ): SubscriptionInvoiceNotification {
        $notification = DB::transaction(
            function () use (
                $invoice,
                $failedNotification,
                $requestedByUserId
            ): SubscriptionInvoiceNotification {
                $lockedInvoice =
                    SubscriptionInvoice::query()
                        ->whereKey(
                            $invoice->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $lockedNotification =
                    SubscriptionInvoiceNotification::query()
                        ->whereKey(
                            $failedNotification->id
                        )
                        ->where(
                            'organization_id',
                            $lockedInvoice->organization_id
                        )
                        ->where(
                            'subscription_invoice_id',
                            $lockedInvoice->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                abort_unless(
                    $lockedNotification->isFailed(),
                    422,
                    'Only failed reminder attempts can be retried.'
                );

                $retryMinutes = max(
                    1,
                    (int) config(
                        'subscription-invoice-notifications.manual_retry_minutes',
                        5
                    )
                );

                $recentRetryExists =
                    SubscriptionInvoiceNotification::query()
                        ->where(
                            'subscription_invoice_id',
                            $lockedInvoice->id
                        )
                        ->where(
                            'reminder_key',
                            $lockedNotification->reminder_key
                        )
                        ->whereNotNull(
                            'retry_of_notification_id'
                        )
                        ->where(
                            'created_at',
                            '>=',
                            now()->subMinutes(
                                $retryMinutes
                            )
                        )
                        ->exists();

                abort_if(
                    $recentRetryExists,
                    422,
                    'This reminder was retried recently.'
                );

                $lockedInvoice->load([
                    'organization',
                    'plan',
                    'subscription.billingOwner',
                ]);

                $recipient =
                    $this->eligibleRecipient(
                        $lockedInvoice
                    );

                abort_if(
                    $recipient === null,
                    422,
                    'No eligible billing recipient is available.'
                );

                $mailable =
                    new SubscriptionInvoiceReminderMail(
                        invoice:
                            $lockedInvoice,

                        reminderKey:
                            $lockedNotification->reminder_key
                    );

                $retry =
                    SubscriptionInvoiceNotification::query()
                        ->create([
                            'organization_id' =>
                                $lockedInvoice->organization_id,

                            'subscription_invoice_id' =>
                                $lockedInvoice->id,

                            'recipient_user_id' =>
                                $recipient->id,

                            'retry_of_notification_id' =>
                                $lockedNotification->id,

                            'retry_requested_by_user_id' =>
                                $requestedByUserId,

                            'reminder_key' =>
                                $lockedNotification->reminder_key,

                            'status' =>
                                SubscriptionInvoiceNotification::STATUS_PROCESSING,

                            'recipient_email' =>
                                trim(
                                    (string) $recipient->email
                                ),

                            'subject' =>
                                $mailable->subjectLine(),

                            'message' =>
                                $mailable->introMessage(),

                            'scheduled_for' =>
                                now(),

                            'sent_at' => null,
                            'failed_at' => null,
                            'error_message' => null,

                            'metadata' =>
                                array_merge(
                                    $lockedNotification->metadata
                                        ?? [],
                                    [
                                        'invoice_number' =>
                                            $lockedInvoice->invoice_number,

                                        'due_date' =>
                                            $lockedInvoice
                                                ->due_date
                                                ?->toDateString(),

                                        'total_amount' =>
                                            $lockedInvoice->total_amount,

                                        'currency' =>
                                            $lockedInvoice->currency,

                                        'plan_name' =>
                                            $lockedInvoice
                                                ->plan
                                                ?->name,

                                        'retry_of_notification_id' =>
                                            $lockedNotification->id,

                                        'retry_requested_by_user_id' =>
                                            $requestedByUserId,
                                    ]
                                ),
                        ]);

                $this->activityLogger->log(
                    action:
                        'organization.subscription_invoice_reminder_retried',

                    description:
                        'A failed subscription invoice reminder was retried.',

                    subject:
                        $retry,

                    organizationId:
                        $lockedInvoice->organization_id,

                    properties: [
                        'invoice_id' =>
                            $lockedInvoice->id,

                        'invoice_number' =>
                            $lockedInvoice->invoice_number,

                        'original_notification_id' =>
                            $lockedNotification->id,

                        'retry_notification_id' =>
                            $retry->id,

                        'reminder_key' =>
                            $lockedNotification->reminder_key,

                        'recipient_email' =>
                            $retry->recipient_email,

                        'requested_by_user_id' =>
                            $requestedByUserId,
                    ],
                );

                return $retry;
            },
            3
        );

        $invoiceForMail =
            SubscriptionInvoice::query()
                ->with([
                    'organization',
                    'plan',
                    'subscription.billingOwner',
                ])
                ->findOrFail(
                    $invoice->id
                );

        try {
            Mail::to(
                $notification->recipient_email
            )->send(
                new SubscriptionInvoiceReminderMail(
                    invoice:
                        $invoiceForMail,

                    reminderKey:
                        $notification->reminder_key
                )
            );

            $notification->forceFill([
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_SENT,

                'sent_at' =>
                    now(),

                'failed_at' => null,
                'error_message' => null,
            ])->save();
        } catch (Throwable $exception) {
            report($exception);

            $notification->forceFill([
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,

                'sent_at' => null,

                'failed_at' =>
                    now(),

                'error_message' =>
                    mb_substr(
                        $exception->getMessage(),
                        0,
                        2000
                    ),
            ])->save();
        }

        return $notification->refresh();
    }
}
