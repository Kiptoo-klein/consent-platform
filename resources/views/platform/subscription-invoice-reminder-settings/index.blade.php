<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    Subscription Invoice Reminder Settings
                </h1>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Configure automatic invoice reminder milestones and retry cooldowns.
                </p>
            </div>

            <a
                href="{{ route('platform.dashboard') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
                Platform Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">
                        The reminder settings could not be saved.
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route(
                    'platform.subscription-invoice-reminder-settings.update'
                ) }}"
                class="space-y-6"
            >
                @csrf
                @method('PATCH')

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start gap-3">
                        <input
                            type="hidden"
                            name="automatic_reminders_enabled"
                            value="0"
                        >

                        <input
                            id="automatic_reminders_enabled"
                            type="checkbox"
                            name="automatic_reminders_enabled"
                            value="1"
                            @checked(
                                (string) old(
                                    'automatic_reminders_enabled',
                                    $settings['automatic_reminders_enabled']
                                        ? '1'
                                        : '0'
                                ) === '1'
                            )
                            class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >

                        <div>
                            <label
                                for="automatic_reminders_enabled"
                                class="font-semibold text-gray-900 dark:text-gray-100"
                            >
                                Enable automatic invoice reminders
                            </label>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                When disabled, the scheduled command exits without sending or recording reminders.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:grid-cols-2">
                    <div>
                        <label
                            for="before_due_days"
                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Before-due reminder days
                        </label>

                        <input
                            id="before_due_days"
                            type="text"
                            name="before_due_days"
                            value="{{ old(
                                'before_due_days',
                                implode(
                                    ', ',
                                    $settings['before_due_days']
                                )
                            ) }}"
                            placeholder="7, 3, 1"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Unique day values from 1 to 365, separated by commas.
                        </p>
                    </div>

                    <div>
                        <label
                            for="overdue_days"
                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Overdue reminder days
                        </label>

                        <input
                            id="overdue_days"
                            type="text"
                            name="overdue_days"
                            value="{{ old(
                                'overdue_days',
                                implode(
                                    ', ',
                                    $settings['overdue_days']
                                )
                            ) }}"
                            placeholder="14, 7, 1"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Unique day values from 1 to 365, separated by commas.
                        </p>
                    </div>
                </section>

                <section class="grid gap-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:grid-cols-2">
                    <div>
                        <label
                            for="automatic_retry_minutes"
                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Automatic retry cooldown
                        </label>

                        <div class="mt-2 flex items-center gap-3">
                            <input
                                id="automatic_retry_minutes"
                                type="number"
                                name="automatic_retry_minutes"
                                min="1"
                                max="10080"
                                value="{{ old(
                                    'automatic_retry_minutes',
                                    $settings['automatic_retry_minutes']
                                ) }}"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >

                            <span class="text-sm text-gray-500">
                                minutes
                            </span>
                        </div>
                    </div>

                    <div>
                        <label
                            for="manual_retry_minutes"
                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Manual retry cooldown
                        </label>

                        <div class="mt-2 flex items-center gap-3">
                            <input
                                id="manual_retry_minutes"
                                type="number"
                                name="manual_retry_minutes"
                                min="1"
                                max="10080"
                                value="{{ old(
                                    'manual_retry_minutes',
                                    $settings['manual_retry_minutes']
                                ) }}"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            >

                            <span class="text-sm text-gray-500">
                                minutes
                            </span>
                        </div>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Save Reminder Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
