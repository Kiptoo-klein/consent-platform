<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                    Subscription Plans
                </h1>

                <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Review plan limits, set KES rates, and configure
                    discounted annual billing.
                </p>
            </div>

            <a
                href="{{ route(
                    'platform.billing.index'
                ) }}"
                class="inline-flex w-fit rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800"
            >
                Billing Management
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-950 via-indigo-900 to-teal-800 p-6 text-white shadow-lg sm:p-8">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-100">
                    Pricing configuration
                </p>

                <h2 class="mt-2 text-2xl font-bold">
                    Monthly and annual subscription rates
                </h2>

                <p class="mt-3 max-w-4xl text-sm font-medium leading-7 text-indigo-50">
                    Enter a monthly price for each plan. When annual billing
                    is enabled, the platform calculates twelve months minus
                    the selected annual discount. A 10% or 15% discount can
                    be entered independently for each plan.
                </p>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-inset ring-white/20">
                        <p class="text-xs font-bold uppercase tracking-wide text-indigo-100">
                            Default currency
                        </p>

                        <p class="mt-2 text-xl font-extrabold">
                            KES
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-inset ring-white/20">
                        <p class="text-xs font-bold uppercase tracking-wide text-indigo-100">
                            Annual calculation
                        </p>

                        <p class="mt-2 text-sm font-bold leading-6">
                            Monthly rate × 12 − discount
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-inset ring-white/20">
                        <p class="text-xs font-bold uppercase tracking-wide text-indigo-100">
                            Existing subscriptions
                        </p>

                        <p class="mt-2 text-sm font-bold leading-6">
                            Editing a plan does not delete organization data.
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                @foreach ($plans as $plan)
                    @php
                        $submittedPlan =
                            (int) old(
                                'editing_plan_id',
                                0
                            )
                            === (int) $plan->id;

                        $fieldValue =
                            static function (
                                string $field,
                                mixed $current
                            ) use (
                                $submittedPlan
                            ): mixed {
                                return $submittedPlan
                                    ? old(
                                        $field,
                                        $current
                                    )
                                    : $current;
                            };

                        $annualPrice =
                            $plan->annualPrice();

                        $annualSavings =
                            $plan->annualSavings();
                    @endphp

                    <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="border-b border-gray-200 bg-gray-50 px-6 py-5 dark:border-gray-800 dark:bg-gray-950 sm:px-7">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-xl font-bold text-gray-950 dark:text-white">
                                            {{ $plan->name }}
                                        </h2>

                                        <span
                                            @class([
                                                'rounded-full px-3 py-1 text-xs font-bold',
                                                'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200' =>
                                                    $plan->is_active,
                                                'bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-200' =>
                                                    ! $plan->is_active,
                                            ])
                                        >
                                            {{ $plan->is_active
                                                ? 'Active'
                                                : 'Inactive' }}
                                        </span>
                                    </div>

                                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                                        {{ $plan->slug }}
                                    </p>

                                    <p class="mt-3 text-sm font-medium leading-6 text-gray-700 dark:text-gray-300">
                                        {{ $plan->description
                                            ?: 'No plan description has been entered.' }}
                                    </p>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-right dark:border-gray-700 dark:bg-gray-900">
                                    <p class="text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                                        Organizations
                                    </p>

                                    <p class="mt-1 text-2xl font-extrabold text-gray-950 dark:text-white">
                                        {{ number_format(
                                            $plan->subscriptions_count
                                        ) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid gap-3 border-b border-gray-200 p-6 dark:border-gray-800 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                            data-plan-limit-summary="spacious"
                        >
                            @foreach ([
                                [
                                    'label' => 'Total users',
                                    'value' => $plan->max_users,
                                ],
                                [
                                    'label' => 'Managers',
                                    'value' => $plan->max_consent_managers,
                                ],
                                [
                                    'label' => 'Staff',
                                    'value' => $plan->max_staff,
                                ],
                                [
                                    'label' => 'Auditors',
                                    'value' => $plan->max_auditors,
                                ],
                                [
                                    'label' => 'Kiosks',
                                    'value' => $plan->max_active_kiosks,
                                ],
                                [
                                    'label' => 'Templates',
                                    'value' =>
                                        $plan->max_consent_templates,
                                ],
                                [
                                    'label' => 'Signed / period',
                                    'value' =>
                                        $plan
                                            ->max_signed_consents_per_period,
                                ],
                            ] as $limit)
                                <div class="min-h-28 rounded-2xl border border-gray-200 bg-gray-50 p-4 text-left dark:border-gray-700 dark:bg-gray-950">
                                    <p class="min-h-8 text-xs font-bold uppercase leading-4 tracking-wide text-gray-600 dark:text-gray-400">
                                        {{ $limit['label'] }}
                                    </p>

                                    <p class="mt-3 break-words text-2xl font-extrabold tabular-nums text-gray-950 dark:text-white">
                                        @if ($limit['value'] === null)
                                            Unlimited
                                        @else
                                            {{ number_format(
                                                $limit['value']
                                            ) }}
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <div class="grid gap-4 border-b border-gray-200 bg-teal-50/50 p-6 dark:border-gray-800 dark:bg-teal-950/20 sm:grid-cols-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                                    Monthly rate
                                </p>

                                <p class="mt-2 text-lg font-extrabold text-gray-950 dark:text-white">
                                    @if ($plan->monthly_price !== null)
                                        {{ $plan->currency }}
                                        {{ number_format(
                                            (float) $plan->monthly_price,
                                            2
                                        ) }}
                                    @else
                                        Not configured
                                    @endif
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                                    Annual discount
                                </p>

                                <p class="mt-2 text-lg font-extrabold text-gray-950 dark:text-white">
                                    @if ($plan->annual_billing_enabled)
                                        {{ number_format(
                                            (float) $plan
                                                ->annual_discount_percent,
                                            2
                                        ) }}%
                                    @else
                                        Disabled
                                    @endif
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                                    Annual rate
                                </p>

                                <p class="mt-2 text-lg font-extrabold text-gray-950 dark:text-white">
                                    @if ($annualPrice !== null)
                                        {{ $plan->currency }}
                                        {{ number_format(
                                            (float) $annualPrice,
                                            2
                                        ) }}
                                    @else
                                        Not available
                                    @endif
                                </p>

                                @if ($annualSavings !== null)
                                    <p class="mt-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                                        Saves {{ $plan->currency }}
                                        {{ number_format(
                                            (float) $annualSavings,
                                            2
                                        ) }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <form
                            method="POST"
                            action="{{ route(
                                'platform.subscription-plans.update',
                                $plan
                            ) }}"
                            class="p-6 sm:p-7"
                        >
                            @csrf
                            @method('PATCH')

                            <input
                                type="hidden"
                                name="editing_plan_id"
                                value="{{ $plan->id }}"
                            >

                            @if (
                                $submittedPlan
                                && $errors->any()
                            )
                                <div class="mb-6 rounded-xl border-2 border-red-300 bg-red-50 p-4 text-red-900 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">
                                    <p class="font-bold">
                                        The plan could not be updated.
                                    </p>

                                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm font-medium">
                                        @foreach (
                                            $errors->all()
                                            as $message
                                        )
                                            <li>
                                                {{ $message }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label
                                        for="plan_name_{{ $plan->id }}"
                                        class="block text-sm font-bold text-gray-950 dark:text-white"
                                    >
                                        Plan name
                                    </label>

                                    <input
                                        id="plan_name_{{ $plan->id }}"
                                        name="name"
                                        type="text"
                                        required
                                        maxlength="150"
                                        value="{{ $fieldValue(
                                            'name',
                                            $plan->name
                                        ) }}"
                                        class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                    >
                                </div>

                                <div>
                                    <label
                                        for="sort_order_{{ $plan->id }}"
                                        class="block text-sm font-bold text-gray-950 dark:text-white"
                                    >
                                        Display order
                                    </label>

                                    <input
                                        id="sort_order_{{ $plan->id }}"
                                        name="sort_order"
                                        type="number"
                                        min="0"
                                        max="9999"
                                        required
                                        value="{{ $fieldValue(
                                            'sort_order',
                                            $plan->sort_order
                                        ) }}"
                                        class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                    >
                                </div>

                                <div class="sm:col-span-2">
                                    <label
                                        for="description_{{ $plan->id }}"
                                        class="block text-sm font-bold text-gray-950 dark:text-white"
                                    >
                                        Description
                                    </label>

                                    <textarea
                                        id="description_{{ $plan->id }}"
                                        name="description"
                                        rows="3"
                                        maxlength="5000"
                                        class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                    >{{ $fieldValue(
                                        'description',
                                        $plan->description
                                    ) }}</textarea>
                                </div>
                            </div>

                            <div class="mt-6 rounded-2xl border border-teal-200 bg-teal-50 p-5 dark:border-teal-900 dark:bg-teal-950/20">
                                <h3 class="font-bold text-gray-950 dark:text-white">
                                    Pricing
                                </h3>

                                <div class="mt-4 grid gap-5 sm:grid-cols-3">
                                    <div>
                                        <label
                                            for="monthly_price_{{ $plan->id }}"
                                            class="block text-sm font-bold text-gray-950 dark:text-white"
                                        >
                                            Monthly rate
                                        </label>

                                        <input
                                            id="monthly_price_{{ $plan->id }}"
                                            name="monthly_price"
                                            type="number"
                                            min="0.01"
                                            max="999999999.99"
                                            step="0.01"
                                            value="{{ $fieldValue(
                                                'monthly_price',
                                                $plan->monthly_price
                                            ) }}"
                                            placeholder="Enter rate"
                                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                        >
                                    </div>

                                    <div>
                                        <label
                                            for="currency_{{ $plan->id }}"
                                            class="block text-sm font-bold text-gray-950 dark:text-white"
                                        >
                                            Currency
                                        </label>

                                        <input
                                            id="currency_{{ $plan->id }}"
                                            name="currency"
                                            type="text"
                                            maxlength="3"
                                            required
                                            value="{{ $fieldValue(
                                                'currency',
                                                $plan->currency
                                                    ?: 'KES'
                                            ) }}"
                                            class="mt-2 block w-full rounded-xl border-gray-300 uppercase shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                        >
                                    </div>

                                    <div>
                                        <label
                                            for="annual_discount_{{ $plan->id }}"
                                            class="block text-sm font-bold text-gray-950 dark:text-white"
                                        >
                                            Annual discount %
                                        </label>

                                        <input
                                            id="annual_discount_{{ $plan->id }}"
                                            name="annual_discount_percent"
                                            type="number"
                                            min="0"
                                            max="50"
                                            step="0.01"
                                            required
                                            value="{{ $fieldValue(
                                                'annual_discount_percent',
                                                $plan
                                                    ->annual_discount_percent
                                            ) }}"
                                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                        >
                                    </div>
                                </div>

                                <input
                                    type="hidden"
                                    name="annual_billing_enabled"
                                    value="0"
                                >

                                <label class="mt-5 flex items-start gap-3 rounded-xl border border-teal-200 bg-white p-4 dark:border-teal-900 dark:bg-gray-950">
                                    <input
                                        name="annual_billing_enabled"
                                        type="checkbox"
                                        value="1"
                                        @checked(
                                            (bool) $fieldValue(
                                                'annual_billing_enabled',
                                                $plan
                                                    ->annual_billing_enabled
                                            )
                                        )
                                        class="mt-1 rounded border-gray-300 text-teal-700 focus:ring-teal-600"
                                    >

                                    <span>
                                        <span class="block font-bold text-gray-950 dark:text-white">
                                            Enable annual billing
                                        </span>

                                        <span class="mt-1 block text-sm leading-6 text-gray-700 dark:text-gray-300">
                                            The annual rate is calculated from
                                            twelve monthly payments minus the
                                            annual discount.
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <div
                                class="mt-6 space-y-5"
                                data-plan-limit-layout="spacious"
                            >
                                <div>
                                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                                        Plan limits
                                    </h3>

                                    <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-700 dark:text-gray-300">
                                        Configure the capacity included with this subscription.
                                        Larger fields and grouped controls make each value easier
                                        to review before saving.
                                    </p>
                                </div>

                                <section class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-950/70 sm:p-6">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h4 class="font-bold text-gray-950 dark:text-white">
                                                People and workspace limits
                                            </h4>

                                            <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">
                                                Total users must include one Organization Admin plus
                                                all configured Consent Manager, Staff, and Auditor seats.
                                            </p>
                                        </div>

                                        <span class="inline-flex w-fit rounded-full bg-gray-200 px-3 py-1 text-xs font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                            Required values
                                        </span>
                                    </div>

                                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                        @foreach ([
                                            [
                                                'field' => 'max_users',
                                                'label' => 'Total users',
                                                'value' => $plan->max_users,
                                                'help' =>
                                                    'Includes the Organization Admin and all other users.',
                                            ],
                                            [
                                                'field' => 'max_consent_managers',
                                                'label' => 'Consent Managers',
                                                'value' => $plan->max_consent_managers,
                                                'help' =>
                                                    'Users who create and manage consent workflows.',
                                            ],
                                            [
                                                'field' => 'max_staff',
                                                'label' => 'Staff',
                                                'value' => $plan->max_staff,
                                                'help' =>
                                                    'Operational users assigned to day-to-day workflows.',
                                            ],
                                            [
                                                'field' => 'max_auditors',
                                                'label' => 'Auditors',
                                                'value' => $plan->max_auditors,
                                                'help' =>
                                                    'Users with review and audit responsibilities.',
                                            ],
                                            [
                                                'field' => 'max_active_kiosks',
                                                'label' => 'Active kiosks',
                                                'value' => $plan->max_active_kiosks,
                                                'help' =>
                                                    'Signing stations that may be active at the same time.',
                                            ],
                                        ] as $limit)
                                            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                                                <label
                                                    for="{{ $limit['field'] }}_{{ $plan->id }}"
                                                    class="block text-sm font-bold text-gray-950 dark:text-white"
                                                >
                                                    {{ $limit['label'] }}
                                                </label>

                                                <p class="mt-1 min-h-10 text-xs leading-5 text-gray-600 dark:text-gray-400">
                                                    {{ $limit['help'] }}
                                                </p>

                                                <input
                                                    id="{{ $limit['field'] }}_{{ $plan->id }}"
                                                    name="{{ $limit['field'] }}"
                                                    type="number"
                                                    inputmode="numeric"
                                                    min="{{ $limit['field'] === 'max_users' ? 1 : 0 }}"
                                                    max="65535"
                                                    required
                                                    value="{{ $fieldValue(
                                                        $limit['field'],
                                                        $limit['value']
                                                    ) }}"
                                                    class="mt-3 block h-12 w-full rounded-xl border-gray-300 px-4 text-lg font-extrabold tabular-nums shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                                >
                                            </div>
                                        @endforeach
                                    </div>
                                </section>

                                <section class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-5 dark:border-indigo-900 dark:bg-indigo-950/20 sm:p-6">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h4 class="font-bold text-gray-950 dark:text-white">
                                                Consent usage limits
                                            </h4>

                                            <p class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                                Leave a field blank for unlimited usage. Enter 0 to
                                                disable that activity for organizations on this plan.
                                            </p>
                                        </div>

                                        <span class="inline-flex w-fit rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100">
                                            Unlimited when blank
                                        </span>
                                    </div>

                                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                        @foreach ([
                                            [
                                                'field' =>
                                                    'max_consent_templates',
                                                'label' =>
                                                    'Consent templates',
                                                'value' =>
                                                    $plan->max_consent_templates,
                                                'help' =>
                                                    'Maximum non-archived templates the organization may keep.',
                                                'placeholder' =>
                                                    'Unlimited',
                                            ],
                                            [
                                                'field' =>
                                                    'max_signed_consents_per_period',
                                                'label' =>
                                                    'Signed consents per period',
                                                'value' =>
                                                    $plan
                                                        ->max_signed_consents_per_period,
                                                'help' =>
                                                    'Completed consents allowed during each billing period.',
                                                'placeholder' =>
                                                    'Unlimited',
                                            ],
                                        ] as $limit)
                                            <div class="rounded-2xl border border-indigo-200 bg-white p-4 dark:border-indigo-900 dark:bg-gray-900">
                                                <label
                                                    for="{{ $limit['field'] }}_{{ $plan->id }}"
                                                    class="block text-sm font-bold text-gray-950 dark:text-white"
                                                >
                                                    {{ $limit['label'] }}
                                                </label>

                                                <p class="mt-1 min-h-10 text-xs leading-5 text-gray-600 dark:text-gray-400">
                                                    {{ $limit['help'] }}
                                                </p>

                                                <input
                                                    id="{{ $limit['field'] }}_{{ $plan->id }}"
                                                    name="{{ $limit['field'] }}"
                                                    type="number"
                                                    inputmode="numeric"
                                                    min="0"
                                                    max="1000000000"
                                                    placeholder="{{ $limit['placeholder'] }}"
                                                    value="{{ $fieldValue(
                                                        $limit['field'],
                                                        $limit['value']
                                                    ) }}"
                                                    class="mt-3 block h-12 w-full rounded-xl border-indigo-200 px-4 text-lg font-extrabold tabular-nums shadow-sm placeholder:text-sm placeholder:font-semibold focus:border-indigo-600 focus:ring-indigo-600 dark:border-indigo-900 dark:bg-gray-950 dark:text-white"
                                                >

                                                <p class="mt-2 text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                                                    Blank = unlimited · 0 = disabled
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            </div>
                            <div class="mt-6 flex flex-col gap-4 border-t border-gray-200 pt-6 dark:border-gray-800 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <input
                                        type="hidden"
                                        name="is_active"
                                        value="0"
                                    >

                                    <label class="flex items-center gap-3">
                                        <input
                                            name="is_active"
                                            type="checkbox"
                                            value="1"
                                            @checked(
                                                (bool) $fieldValue(
                                                    'is_active',
                                                    $plan->is_active
                                                )
                                            )
                                            class="rounded border-gray-300 text-teal-700 focus:ring-teal-600"
                                        >

                                        <span class="font-bold text-gray-950 dark:text-white">
                                            Plan is available for assignment
                                        </span>
                                    </label>

                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                        Deactivating a plan hides it from new
                                        assignments but does not remove
                                        existing subscriptions.
                                    </p>
                                </div>

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"
                                >
                                    Save Plan
                                </button>
                            </div>
                        </form>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
