<?php

namespace App\Services;

use App\Models\OrganizationInvoiceReminderPreference;
use Illuminate\Support\Facades\Cache;

class OrganizationInvoiceReminderPreferenceService
{
    /**
     * @return array{
     *     before_due_reminders_enabled: bool,
     *     overdue_reminders_enabled: bool
     * }
     */
    public function preferencesFor(
        int $organizationId
    ): array {
        return Cache::rememberForever(
            $this->cacheKey(
                $organizationId
            ),
            function () use (
                $organizationId
            ): array {
                $preference =
                    OrganizationInvoiceReminderPreference::query()
                        ->where(
                            'organization_id',
                            $organizationId
                        )
                        ->first();

                if ($preference === null) {
                    return [
                        'before_due_reminders_enabled' =>
                            true,

                        'overdue_reminders_enabled' =>
                            true,
                    ];
                }

                return [
                    'before_due_reminders_enabled' =>
                        (bool) $preference
                            ->before_due_reminders_enabled,

                    'overdue_reminders_enabled' =>
                        (bool) $preference
                            ->overdue_reminders_enabled,
                ];
            }
        );
    }

    public function beforeDueRemindersEnabled(
        int $organizationId
    ): bool {
        return $this->preferencesFor(
            $organizationId
        )['before_due_reminders_enabled'];
    }

    public function overdueRemindersEnabled(
        int $organizationId
    ): bool {
        return $this->preferencesFor(
            $organizationId
        )['overdue_reminders_enabled'];
    }

    public function allowsReminder(
        int $organizationId,
        string $reminderKey
    ): bool {
        if (
            str_starts_with(
                $reminderKey,
                'due_in_'
            )
        ) {
            return $this
                ->beforeDueRemindersEnabled(
                    $organizationId
                );
        }

        if (
            str_starts_with(
                $reminderKey,
                'overdue_'
            )
        ) {
            return $this
                ->overdueRemindersEnabled(
                    $organizationId
                );
        }

        return true;
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
        return 'organization_invoice_reminder_preferences:'
            .$organizationId;
    }
}
