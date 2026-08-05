<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Kiosk Limit Reached
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Manage your active kiosk allowance.
                </p>
            </div>

            <a
                href="{{ route('signing-stations.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
            >
                Back to Kiosks
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-2xl border border-amber-300 bg-white shadow-sm">
                <div class="border-b border-amber-200 bg-amber-50 px-6 py-5">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">
                        Subscription capacity reached
                    </p>

                    <h1 class="mt-2 text-2xl font-bold text-gray-950">
                        Your active kiosk limit has been reached
                    </h1>
                </div>

                <div class="space-y-6 p-6">
                    <p class="leading-7 text-gray-700">
                        Your
                        <strong>{{ $kioskCapacity['plan_name'] ?? 'current' }}</strong>
                        plan allows
                        <strong>{{ $kioskCapacity['limit'] }}</strong>
                        active
                        {{ \Illuminate\Support\Str::plural(
                            'kiosk',
                            (int) $kioskCapacity['limit']
                        ) }}.
                        You currently have
                        <strong>{{ $kioskCapacity['used'] }}</strong>
                        active.
                    </p>

                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 text-blue-950">
                        <h2 class="font-bold">
                            Available choices
                        </h2>

                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6">
                            <li>Edit or continue using an existing kiosk.</li>
                            <li>Pause an active kiosk before activating another.</li>
                            <li>Upgrade to a plan with more active kiosks.</li>
                        </ul>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        @if ($existingStation !== null)
                            <a
                                href="{{ route(
                                    'signing-stations.edit',
                                    $existingStation
                                ) }}"
                                class="inline-flex items-center justify-center rounded-lg bg-indigo-700 px-5 py-3 font-semibold text-white hover:bg-indigo-800"
                            >
                                Edit Existing Kiosk
                            </a>
                        @endif

                        @if (
                            \Illuminate\Support\Facades\Route::has(
                                'organization-subscription-plans.index'
                            )
                        )
                            <a
                                href="{{ route(
                                    'organization-subscription-plans.index'
                                ) }}"
                                class="inline-flex items-center justify-center rounded-lg border border-indigo-300 bg-indigo-50 px-5 py-3 font-semibold text-indigo-800 hover:bg-indigo-100"
                            >
                                View Upgrade Plans
                            </a>
                        @endif

                        <a
                            href="{{ route('signing-stations.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            View All Kiosks
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
