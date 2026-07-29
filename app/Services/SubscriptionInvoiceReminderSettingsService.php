<?php

namespace App\Services;

use App\Models\SubscriptionInvoiceReminderSetting;
use Illuminate\Support\Facades\Cache;

class SubscriptionInvoiceReminderSettingsService
{
    public const CACHE_KEY =
        'subscription_invoice_reminder_settings';

    /**
     * @return array{
     *     automatic_reminders_enabled: bool,
     *     before_due_days: array<int, int>,
     *     overdue_days: array<int, int>,
     *     automatic_retry_minutes: int,
     *     manual_retry_minutes: int
     * }
     */
    public function settings(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            function (): array {
                $setting =
                    SubscriptionInvoiceReminderSetting::query()
                        ->where(
                            'singleton_key',
                            SubscriptionInvoiceReminderSetting::SINGLETON_KEY
                        )
                        ->first();

                if ($setting === null) {
                    return $this->configurationFallbacks();
                }

                return [
                    'automatic_reminders_enabled' =>
                        (bool) $setting
                            ->automatic_reminders_enabled,

                    'before_due_days' =>
                        $this->normalizeDays(
                            $setting->before_due_days
                        ),

                    'overdue_days' =>
                        $this->normalizeDays(
                            $setting->overdue_days
                        ),

                    'automatic_retry_minutes' =>
                        max(
                            1,
                            (int) $setting
                                ->automatic_retry_minutes
                        ),

                    'manual_retry_minutes' =>
                        max(
                            1,
                            (int) $setting
                                ->manual_retry_minutes
                        ),
                ];
            }
        );
    }

    public function automaticRemindersEnabled(): bool
    {
        return $this->settings()[
            'automatic_reminders_enabled'
        ];
    }

    /**
     * @return array<int, int>
     */
    public function beforeDueDays(): array
    {
        return $this->settings()[
            'before_due_days'
        ];
    }

    /**
     * @return array<int, int>
     */
    public function overdueDays(): array
    {
        return $this->settings()[
            'overdue_days'
        ];
    }

    public function automaticRetryMinutes(): int
    {
        return $this->settings()[
            'automatic_retry_minutes'
        ];
    }

    public function manualRetryMinutes(): int
    {
        return $this->settings()[
            'manual_retry_minutes'
        ];
    }

    public function forgetCache(): void
    {
        Cache::forget(
            self::CACHE_KEY
        );
    }

    /**
     * @return array{
     *     automatic_reminders_enabled: bool,
     *     before_due_days: array<int, int>,
     *     overdue_days: array<int, int>,
     *     automatic_retry_minutes: int,
     *     manual_retry_minutes: int
     * }
     */
    private function configurationFallbacks(): array
    {
        return [
            'automatic_reminders_enabled' =>
                (bool) config(
                    'subscription-invoice-notifications.enabled',
                    true
                ),

            'before_due_days' =>
                $this->normalizeDays(
                    config(
                        'subscription-invoice-notifications.before_due_days',
                        [3, 1]
                    )
                ),

            'overdue_days' =>
                $this->normalizeDays(
                    config(
                        'subscription-invoice-notifications.overdue_days',
                        [7, 1]
                    )
                ),

            'automatic_retry_minutes' =>
                max(
                    1,
                    (int) config(
                        'subscription-invoice-notifications.automatic_retry_minutes',
                        60
                    )
                ),

            'manual_retry_minutes' =>
                max(
                    1,
                    (int) config(
                        'subscription-invoice-notifications.manual_retry_minutes',
                        5
                    )
                ),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeDays(
        mixed $days
    ): array {
        if (! is_array($days)) {
            return [];
        }

        return collect($days)
            ->map(
                fn ($day): int =>
                    (int) $day
            )
            ->filter(
                fn (int $day): bool =>
                    $day >= 1
                    && $day <= 365
            )
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }
}
