<?php

namespace App\Services;

use App\Models\OrganizationInvoiceReminderRecipient;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class OrganizationInvoiceReminderRecipientService
{
    /**
     * @return list<int>
     */
    public function configuredRecipientUserIds(
        int $organizationId
    ): array {
        return Cache::rememberForever(
            $this->cacheKey(
                $organizationId
            ),
            function () use (
                $organizationId
            ): array {
                return OrganizationInvoiceReminderRecipient::query()
                    ->where(
                        'organization_id',
                        $organizationId
                    )
                    ->orderBy('user_id')
                    ->pluck('user_id')
                    ->map(
                        static fn ($id): int =>
                            (int) $id
                    )
                    ->values()
                    ->all();
            }
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function eligibleAutomaticRecipients(
        SubscriptionInvoice $invoice
    ): Collection {
        $invoice->loadMissing([
            'subscription.billingOwner',
        ]);

        $billingOwnerId =
            $invoice
                ->subscription
                ?->billing_owner_user_id;

        $orderedUserIds =
            collect([
                $billingOwnerId,
                ...$this->configuredRecipientUserIds(
                    (int) $invoice->organization_id
                ),
            ])
                ->filter(
                    static fn ($id): bool =>
                        $id !== null
                )
                ->map(
                    static fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values();

        if ($orderedUserIds->isEmpty()) {
            return new Collection();
        }

        $eligibleUsers =
            User::query()
                ->whereIn(
                    'id',
                    $orderedUserIds->all()
                )
                ->where(
                    'organization_id',
                    $invoice->organization_id
                )
                ->where(
                    'is_active',
                    true
                )
                ->get()
                ->filter(
                    static fn (User $user): bool =>
                        filter_var(
                            trim(
                                (string) $user->email
                            ),
                            FILTER_VALIDATE_EMAIL
                        ) !== false
                )
                ->keyBy(
                    static fn (User $user): int =>
                        (int) $user->id
                );

        return new Collection(
            $orderedUserIds
                ->map(
                    static fn (int $userId): ?User =>
                        $eligibleUsers->get(
                            $userId
                        )
                )
                ->filter()
                ->values()
                ->all()
        );
    }

    public function forgetCache(
        int $organizationId
    ): void {
        Cache::forget(
            $this->cacheKey(
                $organizationId
            )
        );
    }

    private function cacheKey(
        int $organizationId
    ): string {
        return 'organization_invoice_reminder_recipients:'
            .$organizationId;
    }
}
