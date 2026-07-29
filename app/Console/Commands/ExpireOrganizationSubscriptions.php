<?php

namespace App\Console\Commands;

use App\Enums\OrganizationSubscriptionStatus;
use App\Models\OrganizationSubscription;
use App\Services\SubscriptionExpiryService;
use Illuminate\Console\Command;

class ExpireOrganizationSubscriptions extends Command
{
    /**
     * @var string
     */
    protected $signature =
        'subscriptions:expire '
        .'{--chunk=100 : Number of records to process per batch}';

    /**
     * @var string
     */
    protected $description =
        'Expire organization subscriptions whose lifecycle deadlines have passed.';

    public function handle(
        SubscriptionExpiryService $subscriptionExpiryService
    ): int {
        $chunkSize = max(
            1,
            min(
                (int) $this->option('chunk'),
                1000
            )
        );

        $expiredCount = 0;
        $now = now();

        OrganizationSubscription::query()
            ->whereIn(
                'status',
                [
                    OrganizationSubscriptionStatus::TRIALING
                        ->value,

                    OrganizationSubscriptionStatus::ACTIVE
                        ->value,
                ]
            )
            ->where(
                function ($query) use ($now): void {
                    $query
                        ->where(
                            function ($builder) use ($now): void {
                                $builder
                                    ->whereNotNull('ends_at')
                                    ->where(
                                        'ends_at',
                                        '<=',
                                        $now
                                    );
                            }
                        )
                        ->orWhere(
                            function ($builder) use ($now): void {
                                $builder
                                    ->where(
                                        'status',
                                        OrganizationSubscriptionStatus::TRIALING
                                            ->value
                                    )
                                    ->whereNotNull(
                                        'trial_ends_at'
                                    )
                                    ->where(
                                        'trial_ends_at',
                                        '<=',
                                        $now
                                    );
                            }
                        )
                        ->orWhere(
                            function ($builder) use ($now): void {
                                $builder
                                    ->where(
                                        'status',
                                        OrganizationSubscriptionStatus::ACTIVE
                                            ->value
                                    )
                                    ->whereNotNull(
                                        'current_period_ends_at'
                                    )
                                    ->where(
                                        'current_period_ends_at',
                                        '<=',
                                        $now
                                    );
                            }
                        );
                }
            )
            ->orderBy('id')
            ->chunkById(
                $chunkSize,
                function ($subscriptions) use (
                    $subscriptionExpiryService,
                    &$expiredCount
                ): void {
                    foreach (
                        $subscriptions
                        as $organizationSubscription
                    ) {
                        $wasExpired =
                            $subscriptionExpiryService
                                ->expireIfDue(
                                    organizationSubscription:
                                        $organizationSubscription,

                                    source:
                                        'scheduled_command'
                                );

                        if ($wasExpired) {
                            $expiredCount++;
                        }
                    }
                }
            );

        $this->info(
            $expiredCount === 1
                ? 'Expired 1 organization subscription.'
                : "Expired {$expiredCount} organization subscriptions."
        );

        return self::SUCCESS;
    }
}
