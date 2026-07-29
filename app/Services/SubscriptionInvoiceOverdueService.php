<?php

namespace App\Services;

use App\Enums\SubscriptionInvoiceStatus;
use App\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\DB;

class SubscriptionInvoiceOverdueService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    /**
     * Lock an invoice and mark it overdue when its due date has passed.
     */
    public function markOverdueIfDue(
        SubscriptionInvoice $subscriptionInvoice,
        string $source
    ): bool {
        return DB::transaction(
            function () use (
                $subscriptionInvoice,
                $source
            ): bool {
                $lockedInvoice =
                    SubscriptionInvoice::query()
                        ->whereKey(
                            $subscriptionInvoice->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                return $this->markLockedOverdueIfDue(
                    subscriptionInvoice:
                        $lockedInvoice,

                    source:
                        $source
                );
            },
            3
        );
    }

    /**
     * Mark an already locked invoice overdue when eligible.
     *
     * Returns true only when this call changes the invoice.
     */
    public function markLockedOverdueIfDue(
        SubscriptionInvoice $subscriptionInvoice,
        string $source
    ): bool {
        if (
            $subscriptionInvoice->status
                !== SubscriptionInvoiceStatus::ISSUED
            || $subscriptionInvoice->due_date === null
            || ! $subscriptionInvoice->due_date
                ->isBefore(today())
        ) {
            return false;
        }

        $previousStatus =
            $subscriptionInvoice->status;

        $markedOverdueAt = now();

        $subscriptionInvoice->update([
            'status' =>
                SubscriptionInvoiceStatus::OVERDUE,
        ]);

        $this->activityLogger->log(
            action:
                'organization.subscription_invoice_marked_overdue',

            description:
                'Subscription invoice marked overdue automatically.',

            subject:
                $subscriptionInvoice,

            organizationId:
                $subscriptionInvoice->organization_id,

            properties: [
                'invoice_number' =>
                    $subscriptionInvoice->invoice_number,

                'overdue_source' =>
                    trim($source) !== ''
                        ? trim($source)
                        : 'system',

                'old' => [
                    'status' =>
                        $previousStatus?->value,
                ],

                'new' => [
                    'status' =>
                        SubscriptionInvoiceStatus::OVERDUE
                            ->value,
                ],

                'issue_date' =>
                    $subscriptionInvoice
                        ->issue_date
                        ?->toDateString(),

                'due_date' =>
                    $subscriptionInvoice
                        ->due_date
                        ?->toDateString(),

                'total_amount' =>
                    $subscriptionInvoice->total_amount,

                'currency' =>
                    $subscriptionInvoice->currency,

                'marked_overdue_at' =>
                    $markedOverdueAt->toIso8601String(),
            ],
        );

        return true;
    }
}
