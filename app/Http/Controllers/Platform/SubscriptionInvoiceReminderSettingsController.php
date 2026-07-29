<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoiceReminderSetting;
use App\Services\ActivityLogger;
use App\Services\SubscriptionInvoiceReminderSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubscriptionInvoiceReminderSettingsController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function index(
        SubscriptionInvoiceReminderSettingsService $settingsService
    ): View {
        return view(
            'platform.subscription-invoice-reminder-settings.index',
            [
                'settings' =>
                    $settingsService->settings(),
            ]
        );
    }

    public function update(
        Request $request,
        SubscriptionInvoiceReminderSettingsService $settingsService
    ): RedirectResponse {
        $validated = $request->validate([
            'automatic_reminders_enabled' => [
                'required',
                'boolean',
            ],

            'before_due_days' => [
                'required',
                'string',

                function (
                    string $attribute,
                    mixed $value,
                    $fail
                ): void {
                    if (
                        $this->parseMilestones(
                            $value
                        ) === null
                    ) {
                        $fail(
                            'Enter unique whole numbers from 1 to 365 separated by commas.'
                        );
                    }
                },
            ],

            'overdue_days' => [
                'required',
                'string',

                function (
                    string $attribute,
                    mixed $value,
                    $fail
                ): void {
                    if (
                        $this->parseMilestones(
                            $value
                        ) === null
                    ) {
                        $fail(
                            'Enter unique whole numbers from 1 to 365 separated by commas.'
                        );
                    }
                },
            ],

            'automatic_retry_minutes' => [
                'required',
                'integer',
                'min:1',
                'max:10080',
            ],

            'manual_retry_minutes' => [
                'required',
                'integer',
                'min:1',
                'max:10080',
            ],
        ]);

        $beforeDueDays =
            $this->parseMilestones(
                $validated['before_due_days']
            );

        $overdueDays =
            $this->parseMilestones(
                $validated['overdue_days']
            );

        abort_if(
            $beforeDueDays === null
            || $overdueDays === null,
            422
        );

        $oldSettings =
            $settingsService->settings();

        $newSettings = [
            'automatic_reminders_enabled' =>
                $request->boolean(
                    'automatic_reminders_enabled'
                ),

            'before_due_days' =>
                $beforeDueDays,

            'overdue_days' =>
                $overdueDays,

            'automatic_retry_minutes' =>
                (int) $validated[
                    'automatic_retry_minutes'
                ],

            'manual_retry_minutes' =>
                (int) $validated[
                    'manual_retry_minutes'
                ],
        ];

        DB::transaction(
            function () use (
                $request,
                $oldSettings,
                $newSettings
            ): void {
                $setting =
                    SubscriptionInvoiceReminderSetting::query()
                        ->where(
                            'singleton_key',
                            SubscriptionInvoiceReminderSetting::SINGLETON_KEY
                        )
                        ->lockForUpdate()
                        ->first();

                if ($setting === null) {
                    $setting =
                        new SubscriptionInvoiceReminderSetting();

                    $setting->singleton_key =
                        SubscriptionInvoiceReminderSetting::SINGLETON_KEY;
                }

                $setting->fill(
                    array_merge(
                        $newSettings,
                        [
                            'updated_by_user_id' =>
                                $request->user()->id,
                        ]
                    )
                );

                $setting->save();

                $this->activityLogger->log(
                    action:
                        'platform.subscription_invoice_reminder_settings_updated',

                    description:
                        'Subscription invoice reminder settings were updated.',

                    subject:
                        $setting,

                    organizationId:
                        null,

                    properties: [
                        'old' =>
                            $oldSettings,

                        'new' =>
                            $newSettings,
                    ],
                );
            },
            3
        );

        $settingsService->forgetCache();

        return redirect()
            ->route(
                'platform.subscription-invoice-reminder-settings.index'
            )
            ->with(
                'success',
                'Subscription invoice reminder settings updated successfully.'
            );
    }

    /**
     * @return array<int, int>|null
     */
    private function parseMilestones(
        mixed $value
    ): ?array {
        if (! is_string($value)) {
            return null;
        }

        $parts = array_map(
            static fn (string $part): string =>
                trim($part),
            explode(',', $value)
        );

        if (
            $parts === []
            || in_array('', $parts, true)
        ) {
            return null;
        }

        $days = [];

        foreach ($parts as $part) {
            if (! ctype_digit($part)) {
                return null;
            }

            $day = (int) $part;

            if (
                $day < 1
                || $day > 365
                || in_array(
                    $day,
                    $days,
                    true
                )
            ) {
                return null;
            }

            $days[] = $day;
        }

        rsort(
            $days,
            SORT_NUMERIC
        );

        return $days;
    }
}
