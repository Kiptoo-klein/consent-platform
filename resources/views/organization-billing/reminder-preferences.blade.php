<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Invoice Reminder Preferences
                </h1>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Choose which automatic subscription invoice reminders your billing owner receives.
                </p>
            </div>

            <a
                href="{{ route('organization-billing.index') }}"
                class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
            >
                Billing &amp; Receipts
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">
                        The reminder preferences could not be saved.
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-8">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                        Organization
                    </p>

                    <h2 class="mt-2 text-xl font-bold text-gray-900 dark:text-white">
                        {{ $organization->name }}
                    </h2>

                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        These settings affect automatic reminders only. Platform administrators may still manually retry failed deliveries.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route(
                        'organization-billing.reminder-preferences.update'
                    ) }}"
                    class="mt-8 space-y-5"
                >
                    @csrf
                    @method('PATCH')

                    <label class="flex items-start gap-4 rounded-2xl border border-gray-200 p-5 dark:border-gray-700">
                        <input
                            type="hidden"
                            name="before_due_reminders_enabled"
                            value="0"
                        >

                        <input
                            type="checkbox"
                            name="before_due_reminders_enabled"
                            value="1"
                            @checked(
                                (string) old(
                                    'before_due_reminders_enabled',
                                    $preferences[
                                        'before_due_reminders_enabled'
                                    ]
                                        ? '1'
                                        : '0'
                                ) === '1'
                            )
                            class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >

                        <span>
                            <span class="block font-semibold text-gray-900 dark:text-white">
                                Before-due reminders
                            </span>

                            <span class="mt-1 block text-sm text-gray-600 dark:text-gray-400">
                                Receive reminders before an issued invoice reaches its due date.
                            </span>
                        </span>
                    </label>

                    <label class="flex items-start gap-4 rounded-2xl border border-gray-200 p-5 dark:border-gray-700">
                        <input
                            type="hidden"
                            name="overdue_reminders_enabled"
                            value="0"
                        >

                        <input
                            type="checkbox"
                            name="overdue_reminders_enabled"
                            value="1"
                            @checked(
                                (string) old(
                                    'overdue_reminders_enabled',
                                    $preferences[
                                        'overdue_reminders_enabled'
                                    ]
                                        ? '1'
                                        : '0'
                                ) === '1'
                            )
                            class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        >

                        <span>
                            <span class="block font-semibold text-gray-900 dark:text-white">
                                Overdue reminders
                            </span>

                            <span class="mt-1 block text-sm text-gray-600 dark:text-gray-400">
                                Receive reminders after an unpaid invoice becomes overdue.
                            </span>
                        </span>
                    </label>

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            class="inline-flex rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                        >
                            Save Reminder Preferences
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
