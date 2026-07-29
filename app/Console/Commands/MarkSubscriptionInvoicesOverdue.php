<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionInvoiceStatus;
use App\Models\SubscriptionInvoice;
use App\Services\SubscriptionInvoiceOverdueService;
use Illuminate\Console\Command;

class MarkSubscriptionInvoicesOverdue extends Command
{
    /**
     * @var string
     */
    protected $signature =
        'subscription-invoices:mark-overdue '
        .'{--chunk=100 : Number of records to process per batch}';

    /**
     * @var string
     */
    protected $description =
        'Mark issued subscription invoices overdue after their due date.';

    public function handle(
        SubscriptionInvoiceOverdueService $overdueService
    ): int {
        $chunkSize = max(
            1,
            min(
                (int) $this->option('chunk'),
                1000
            )
        );

        $overdueCount = 0;
        $today = today()->toDateString();

        SubscriptionInvoice::query()
            ->where(
                'status',
                SubscriptionInvoiceStatus::ISSUED->value
            )
            ->whereNotNull('due_date')
            ->where(
                'due_date',
                '<',
                $today
            )
            ->orderBy('id')
            ->chunkById(
                $chunkSize,
                function ($invoices) use (
                    $overdueService,
                    &$overdueCount
                ): void {
                    foreach (
                        $invoices
                        as $subscriptionInvoice
                    ) {
                        $wasMarkedOverdue =
                            $overdueService
                                ->markOverdueIfDue(
                                    subscriptionInvoice:
                                        $subscriptionInvoice,

                                    source:
                                        'scheduled_command'
                                );

                        if ($wasMarkedOverdue) {
                            $overdueCount++;
                        }
                    }
                }
            );

        $this->info(
            $overdueCount === 1
                ? 'Marked 1 subscription invoice overdue.'
                : "Marked {$overdueCount} subscription invoices overdue."
        );

        return self::SUCCESS;
    }
}
