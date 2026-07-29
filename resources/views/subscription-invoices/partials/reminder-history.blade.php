@php
    $showRetryControls =
        $showRetryControls
        ?? false;

    $reminderLabels = [
        \App\Models\SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS =>
            'Due in 3 days',

        \App\Models\SubscriptionInvoiceNotification::REMINDER_DUE_IN_1_DAY =>
            'Due in 1 day',

        \App\Models\SubscriptionInvoiceNotification::REMINDER_OVERDUE_1_DAY =>
            '1 day overdue',

        \App\Models\SubscriptionInvoiceNotification::REMINDER_OVERDUE_7_DAYS =>
            '7 days overdue',
    ];

    $statusLabels = [
        \App\Models\SubscriptionInvoiceNotification::STATUS_PROCESSING =>
            'Processing',

        \App\Models\SubscriptionInvoiceNotification::STATUS_SENT =>
            'Sent',

        \App\Models\SubscriptionInvoiceNotification::STATUS_FAILED =>
            'Failed',
    ];
@endphp

<section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Reminder History
        </h2>

        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            The latest email reminder attempts recorded for this invoice.
        </p>
    </div>

    @if ($notifications->isEmpty())
        <div class="p-6 text-sm text-gray-500 dark:text-gray-400">
            No reminder attempts have been recorded for this invoice.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Reminder
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Status
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Recipient
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Attempted
                        </th>

                        @if ($showFailureDetails)
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Failure details
                            </th>
                        @endif

                        @if ($showRetryControls)
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Action
                            </th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach ($notifications as $notification)
                        @php
                            $reminderLabel =
                                $reminderLabels[
                                    $notification->reminder_key
                                ]
                                ?? \Illuminate\Support\Str::headline(
                                    $notification->reminder_key
                                );

                            $statusLabel =
                                $statusLabels[
                                    $notification->status
                                ]
                                ?? \Illuminate\Support\Str::headline(
                                    $notification->status
                                );

                            $statusClasses = match (
                                $notification->status
                            ) {
                                \App\Models\SubscriptionInvoiceNotification::STATUS_SENT =>
                                    'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300',

                                \App\Models\SubscriptionInvoiceNotification::STATUS_FAILED =>
                                    'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300',

                                default =>
                                    'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300',
                            };
                        @endphp

                        <tr>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $reminderLabel }}
                            </td>

                            <td class="px-5 py-4 text-sm">
                                <span
                                    class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}"
                                >
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $notification->recipient_email }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $notification->created_at?->format(
                                    'M d, Y H:i'
                                ) ?? '—' }}
                            </td>

                            @if ($showFailureDetails)
                                <td class="max-w-md px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    @if (
                                        $notification->isFailed()
                                        && filled(
                                            $notification->error_message
                                        )
                                    )
                                        <span class="whitespace-pre-wrap break-words text-red-700 dark:text-red-300">
                                            {{ $notification->error_message }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">
                                            —
                                        </span>
                                    @endif
                                </td>
                            @endif

                            @if ($showRetryControls)
                                <td class="whitespace-nowrap px-5 py-4 text-sm">
                                    @if ($notification->isFailed())
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'platform.organizations.subscription-invoices.reminder-notifications.retry',
                                                [
                                                    'organization' =>
                                                        $notification->organization_id,

                                                    'subscriptionInvoice' =>
                                                        $notification->subscription_invoice_id,

                                                    'subscriptionInvoiceNotification' =>
                                                        $notification->id,
                                                ]
                                            ) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="inline-flex rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                                            >
                                                Retry reminder
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-400">
                                            —
                                        </span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
