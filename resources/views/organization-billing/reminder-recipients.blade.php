<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Invoice Reminder Recipients
                </h1>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Choose additional organization users who should receive automatic subscription invoice reminders.
                </p>
            </div>

            <a
                href="{{ route('organization-billing.index') }}"
                class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                Back to Billing
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-medium text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Billing owner
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        The billing owner is always included when the account is active and the email address is valid.
                    </p>
                </div>

                <div class="p-6">
                    <div class="flex flex-col gap-2 rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900 dark:bg-indigo-950/40">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $billingOwner->name }}
                                </p>

                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ $billingOwner->email }}
                                </p>
                            </div>

                            <span class="inline-flex rounded-full border border-indigo-300 bg-white px-3 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                Always included
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <form
                method="POST"
                action="{{ route(
                    'organization-billing.reminder-recipients.update'
                ) }}"
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            >
                @csrf
                @method('PATCH')

                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Additional recipients
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Only active, non-archived users from {{ $organization->name }} are available.
                    </p>
                </div>

                <div class="space-y-4 p-6">
                    @error('recipient_user_ids')
                        <p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('recipient_user_ids.*')
                        <p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                            {{ $message }}
                        </p>
                    @enderror

                    @if ($eligibleUsers->isEmpty())
                        <div class="rounded-xl border border-dashed border-gray-300 p-5 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            There are no eligible additional reminder recipients.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($eligibleUsers as $user)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-4 transition hover:border-teal-300 hover:bg-teal-50/50 dark:border-gray-700 dark:hover:border-teal-700 dark:hover:bg-teal-950/20">
                                    <input
                                        type="checkbox"
                                        name="recipient_user_ids[]"
                                        value="{{ $user->id }}"
                                        @checked(
                                            in_array(
                                                (int) $user->id,
                                                old(
                                                    'recipient_user_ids',
                                                    $configuredRecipientUserIds
                                                ),
                                                true
                                            )
                                        )
                                        class="mt-1 rounded border-gray-300 text-teal-600 shadow-sm focus:ring-teal-500 dark:border-gray-700 dark:bg-gray-800"
                                    >

                                    <span>
                                        <span class="block font-semibold text-gray-900 dark:text-white">
                                            {{ $user->name }}
                                        </span>

                                        <span class="block text-sm text-gray-600 dark:text-gray-300">
                                            {{ $user->email }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-950/40">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Clearing every checkbox restores billing-owner-only delivery.
                    </p>

                    <button
                        type="submit"
                        class="inline-flex rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                    >
                        Save Recipients
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
