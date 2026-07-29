<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    {{ data_get(
                        $organization,
                        'name',
                        'Organization'
                    ) }}
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Organization details and account management.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                @if (\Illuminate\Support\Facades\Route::has(
                    'platform.organizations.users.index'
                ))
                    <a
                        href="{{ route(
                            'platform.organizations.users.index',
                            $organization
                        ) }}"
                        class="inline-flex items-center rounded-lg border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
                    >
                        Manage Users
                    </a>
                @endif

                @if (\Illuminate\Support\Facades\Route::has(
                    'platform.organizations.edit'
                ))
                    <a
                        href="{{ route(
                            'platform.organizations.edit',
                            $organization
                        ) }}"
                        class="inline-flex items-center rounded-lg border border-teal-700 bg-white px-4 py-2 text-sm font-semibold text-teal-700 transition hover:bg-teal-50"
                    >
                        Edit Organization
                    </a>
                @endif

                <a
                    href="{{ route(
                        'platform.organizations.index'
                    ) }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Back to Organizations
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $active = data_get(
            $organization,
            'is_active',
            true
        );

        $userCount = data_get(
            $organization,
            'users_count'
        );

        if (is_null($userCount)) {
            try {
                $userCount = $organization
                    ->users()
                    ->count();
            } catch (\Throwable $exception) {
                $userCount = 0;
            }
        }
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Organization ID
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ data_get($organization, 'id', '—') }}
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Users
                    </p>

                    <p class="mt-3 text-2xl font-bold text-teal-700">
                        {{ number_format((int) $userCount) }}
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Status
                    </p>

                    <div class="mt-3">
                        @if ($active)
                            <span class="inline-flex rounded-full border border-green-200 bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">
                                Active
                            </span>
                        @else
                            <span class="inline-flex rounded-full border border-red-200 bg-red-100 px-3 py-1 text-sm font-semibold text-red-800">
                                Disabled
                            </span>
                        @endif
                    </div>
                </section>
            </div>

            @php
                $subscription = $organization->subscription;
                $plan = $subscription?->plan;
                $hasBypass = $subscription?->hasPlatformBypass() ?? false;
                $hasAccess =
                    $subscription?->allowsOrganizationAccess() ?? false;

                $paymentStatus =
                    $subscription?->payment_status?->value;

                $subscriptionStatus =
                    $subscription?->status?->value;

                $formatStatus = static fn (?string $status): string =>
                    $status
                        ? ucwords(str_replace('_', ' ', $status))
                        : 'Unavailable';
            @endphp

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                Subscription Access
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Review payment access and approve a temporary Platform Admin bypass.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                class="inline-flex w-fit rounded-full border px-3 py-1 text-sm font-semibold
                                    {{ $hasAccess
                                        ? 'border-green-200 bg-green-100 text-green-800'
                                        : 'border-amber-200 bg-amber-100 text-amber-900' }}"
                            >
                                {{ $hasAccess ? 'Access allowed' : 'Access blocked' }}
                            </span>

                            @if ($subscription)
                                <a
                                    href="{{ route(
                                        'platform.organizations.subscription-transactions.index',
                                        $organization
                                    ) }}"
                                    class="inline-flex rounded-lg border border-teal-700 bg-white px-4 py-2 text-sm font-semibold text-teal-700 transition hover:bg-teal-50"
                                >
                                    Payment History
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if (! $subscription)
                    <div class="p-6">
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                            This organization does not have a subscription record.
                        </div>
                    </div>
                @else
                    <div class="grid gap-px bg-gray-200 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="bg-white p-6">
                            <p class="text-sm font-medium text-gray-500">
                                Plan
                            </p>

                            <p class="mt-2 text-lg font-bold text-gray-900">
                                {{ $plan?->name ?? 'Unavailable' }}
                            </p>
                        </div>

                        <div class="bg-white p-6">
                            <p class="text-sm font-medium text-gray-500">
                                Payment
                            </p>

                            <p class="mt-2 text-lg font-bold text-gray-900">
                                {{ $formatStatus($paymentStatus) }}
                            </p>
                        </div>

                        <div class="bg-white p-6">
                            <p class="text-sm font-medium text-gray-500">
                                Subscription
                            </p>

                            <p class="mt-2 text-lg font-bold text-gray-900">
                                {{ $formatStatus($subscriptionStatus) }}
                            </p>
                        </div>

                        <div class="bg-white p-6">
                            <p class="text-sm font-medium text-gray-500">
                                Payment bypass
                            </p>

                            <p class="mt-2 text-lg font-bold text-gray-900">
                                {{ $hasBypass ? 'Approved' : 'Not approved' }}
                            </p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 p-6">
                        <h3 class="text-base font-semibold text-gray-900">
                            Change Subscription Plan
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Changing to a lower-capacity plan does not delete
                            users or pause kiosks. New usage remains blocked
                            until the organization returns within its limits.
                        </p>

                        <form
                            method="POST"
                            action="{{ route(
                                'platform.organizations.subscription-plan.update',
                                $organization
                            ) }}"
                            class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                        >
                            @csrf
                            @method('PATCH')

                            <div class="min-w-0 flex-1">
                                <label
                                    for="subscription_plan_id"
                                    class="block text-sm font-semibold text-gray-900"
                                >
                                    Subscription plan
                                </label>

                                <select
                                    id="subscription_plan_id"
                                    name="subscription_plan_id"
                                    required
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                                >
                                    @foreach ($subscriptionPlans as $availablePlan)
                                        <option
                                            value="{{ $availablePlan->id }}"
                                            @selected(
                                                (int) old(
                                                    'subscription_plan_id',
                                                    $subscription->subscription_plan_id
                                                ) === $availablePlan->id
                                            )
                                        >
                                            {{ $availablePlan->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('subscription_plan_id')
                                    <p class="mt-2 text-sm font-medium text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                class="inline-flex justify-center rounded-lg border border-teal-700 bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800"
                            >
                                Update Plan
                            </button>
                        </form>
                    </div>

                    <div class="border-t border-gray-200 p-6">
                        <h3 class="text-base font-semibold text-gray-900">
                            Renew Subscription
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Confirm payment and define the new active billing
                            period. Renewal preserves the current plan,
                            organization records, and any approved bypass.
                        </p>

                        <form
                            method="POST"
                            action="{{ route(
                                'platform.organizations.subscription-renewal.update',
                                $organization
                            ) }}"
                            class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            @csrf
                            @method('PATCH')

                            <div>
                                <label
                                    for="current_period_starts_at"
                                    class="block text-sm font-semibold text-gray-900"
                                >
                                    Period starts
                                </label>

                                <input
                                    id="current_period_starts_at"
                                    name="current_period_starts_at"
                                    type="datetime-local"
                                    required
                                    value="{{ old(
                                        'current_period_starts_at',
                                        $subscription
                                            ->current_period_starts_at
                                            ?->format('Y-m-d\TH:i')
                                    ) }}"
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                                >

                                @error('current_period_starts_at')
                                    <p class="mt-2 text-sm font-medium text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="current_period_ends_at"
                                    class="block text-sm font-semibold text-gray-900"
                                >
                                    Period ends
                                </label>

                                <input
                                    id="current_period_ends_at"
                                    name="current_period_ends_at"
                                    type="datetime-local"
                                    required
                                    value="{{ old(
                                        'current_period_ends_at',
                                        $subscription
                                            ->current_period_ends_at
                                            ?->format('Y-m-d\TH:i')
                                    ) }}"
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                                >

                                @error('current_period_ends_at')
                                    <p class="mt-2 text-sm font-medium text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="ends_at"
                                    class="block text-sm font-semibold text-gray-900"
                                >
                                    Final subscription end
                                </label>

                                <input
                                    id="ends_at"
                                    name="ends_at"
                                    type="datetime-local"
                                    value="{{ old(
                                        'ends_at',
                                        $subscription
                                            ->ends_at
                                            ?->format('Y-m-d\TH:i')
                                    ) }}"
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                                >

                                <p class="mt-2 text-xs text-gray-500">
                                    Optional. Leave blank for no scheduled final
                                    subscription end.
                                </p>

                                @error('ends_at')
                                    <p class="mt-2 text-sm font-medium text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2 lg:col-span-3">
                                <button
                                    type="submit"
                                    class="inline-flex rounded-lg border border-teal-700 bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800"
                                >
                                    Renew Subscription
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="border-t border-gray-200 p-6">
                        <h3 class="text-base font-semibold text-gray-900">
                            Subscription Lifecycle
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Suspend access temporarily, resume a valid
                            subscription, or cancel it immediately or at the
                            end of its current billing period.
                        </p>

                        <div class="mt-5 grid gap-5 lg:grid-cols-2">
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                                @if (
                                    $subscription->status
                                        === \App\Enums\OrganizationSubscriptionStatus::SUSPENDED
                                )
                                    <h4 class="font-semibold text-amber-950">
                                        Resume Subscription
                                    </h4>

                                    <p class="mt-1 text-sm text-amber-900">
                                        Resumption requires paid status and
                                        lifecycle dates that have not expired.
                                    </p>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'platform.organizations.subscription-suspension.resume',
                                            $organization
                                        ) }}"
                                        class="mt-4"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="inline-flex rounded-lg border border-green-700 bg-green-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-800"
                                        >
                                            Resume Subscription
                                        </button>
                                    </form>
                                @else
                                    <h4 class="font-semibold text-amber-950">
                                        Suspend Subscription
                                    </h4>

                                    <p class="mt-1 text-sm text-amber-900">
                                        Suspension blocks organization access
                                        without changing payment, plan, users,
                                        kiosks, consents, or billing dates.
                                    </p>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'platform.organizations.subscription-suspension.suspend',
                                            $organization
                                        ) }}"
                                        class="mt-4"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <label
                                            for="suspension_reason"
                                            class="block text-sm font-semibold text-amber-950"
                                        >
                                            Suspension reason
                                        </label>

                                        <textarea
                                            id="suspension_reason"
                                            name="reason"
                                            rows="3"
                                            required
                                            maxlength="2000"
                                            class="mt-2 block w-full rounded-lg border-amber-300 bg-white shadow-sm focus:border-amber-600 focus:ring-amber-600"
                                        >{{ old('reason') }}</textarea>

                                        @error('subscription')
                                            <p class="mt-2 text-sm font-medium text-red-700">
                                                {{ $message }}
                                            </p>
                                        @enderror

                                        <button
                                            type="submit"
                                            class="mt-4 inline-flex rounded-lg border border-amber-700 bg-amber-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-800"
                                        >
                                            Suspend Subscription
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <div class="rounded-xl border border-red-200 bg-red-50 p-5">
                                <h4 class="font-semibold text-red-950">
                                    Cancel Subscription
                                </h4>

                                <p class="mt-1 text-sm text-red-900">
                                    Immediate cancellation blocks access now.
                                    Period-end cancellation keeps access until
                                    the current paid period finishes.
                                </p>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'platform.organizations.subscription-cancellation.update',
                                        $organization
                                    ) }}"
                                    class="mt-4"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <label
                                        for="cancellation_mode"
                                        class="block text-sm font-semibold text-red-950"
                                    >
                                        Cancellation timing
                                    </label>

                                    <select
                                        id="cancellation_mode"
                                        name="mode"
                                        required
                                        class="mt-2 block w-full rounded-lg border-red-300 bg-white shadow-sm focus:border-red-600 focus:ring-red-600"
                                    >
                                        <option
                                            value="immediate"
                                            @selected(old('mode') === 'immediate')
                                        >
                                            Cancel immediately
                                        </option>

                                        <option
                                            value="period_end"
                                            @selected(old('mode') === 'period_end')
                                        >
                                            Cancel at period end
                                        </option>
                                    </select>

                                    @error('mode')
                                        <p class="mt-2 text-sm font-medium text-red-700">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                    <label
                                        for="cancellation_reason"
                                        class="mt-4 block text-sm font-semibold text-red-950"
                                    >
                                        Cancellation reason
                                    </label>

                                    <textarea
                                        id="cancellation_reason"
                                        name="reason"
                                        rows="3"
                                        required
                                        maxlength="2000"
                                        class="mt-2 block w-full rounded-lg border-red-300 bg-white shadow-sm focus:border-red-600 focus:ring-red-600"
                                    >{{ old('reason') }}</textarea>

                                    @error('reason')
                                        <p class="mt-2 text-sm font-medium text-red-700">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                    @error('subscription')
                                        <p class="mt-2 text-sm font-medium text-red-700">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                    <button
                                        type="submit"
                                        class="mt-4 inline-flex rounded-lg border border-red-700 bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800"
                                    >
                                        Cancel Subscription
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    @if ($plan && ! empty($capacity))
                        <div class="border-t border-gray-200 p-6">
                            @if ($hasCapacityOverage)
                                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-900">
                                    <h3 class="font-semibold">
                                        Plan capacity exceeded
                                    </h3>

                                    <p class="mt-1 text-sm">
                                        Existing records remain available, but
                                        additional users, role assignments and
                                        active kiosks are restricted.
                                    </p>
                                </div>
                            @endif

                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                @foreach ($capacity as $item)
                                    <div class="rounded-xl border border-gray-200 p-4">
                                        <p class="text-sm font-medium text-gray-500">
                                            {{ $item['label'] }}
                                        </p>

                                        <p class="mt-2 text-xl font-bold text-gray-900">
                                            {{ number_format($item['used']) }}
                                            <span class="text-sm font-semibold text-gray-500">
                                                of {{ number_format($item['limit']) }}
                                            </span>
                                        </p>

                                        @if ($item['overage'] > 0)
                                            <p class="mt-2 text-sm font-semibold text-red-700">
                                                {{ number_format($item['overage']) }}
                                                over limit
                                            </p>
                                        @else
                                            <p class="mt-2 text-sm font-medium text-gray-500">
                                                {{ number_format($item['remaining']) }}
                                                remaining
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="border-t border-gray-200 p-6">
                        @if ($hasBypass)
                            <div class="rounded-xl border border-green-200 bg-green-50 p-5">
                                <h3 class="font-semibold text-green-900">
                                    Payment bypass approved
                                </h3>

                                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="font-medium text-green-700">
                                            Approved by
                                        </dt>

                                        <dd class="mt-1 text-green-950">
                                            {{ $subscription->bypassApprover?->name ?? 'Platform Administrator' }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt class="font-medium text-green-700">
                                            Approved at
                                        </dt>

                                        <dd class="mt-1 text-green-950">
                                            {{ $subscription->bypass_approved_at?->format('M d, Y H:i') ?? '—' }}
                                        </dd>
                                    </div>
                                </dl>

                                <div class="mt-4">
                                    <p class="text-sm font-medium text-green-700">
                                        Reason
                                    </p>

                                    <p class="mt-1 whitespace-pre-line text-sm text-green-950">
                                        {{ $subscription->bypass_reason }}
                                    </p>
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'platform.organizations.subscription-bypass.revoke',
                                        $organization
                                    ) }}"
                                    class="mt-5"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="inline-flex rounded-lg border border-red-700 bg-white px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50"
                                    >
                                        Revoke Payment Bypass
                                    </button>
                                </form>
                            </div>
                        @else
                            <form
                                method="POST"
                                action="{{ route(
                                    'platform.organizations.subscription-bypass.approve',
                                    $organization
                                ) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <label
                                    for="reason"
                                    class="block text-sm font-semibold text-gray-900"
                                >
                                    Approval reason
                                </label>

                                <p class="mt-1 text-sm text-gray-500">
                                    Record why this organization may operate without confirmed payment.
                                </p>

                                <textarea
                                    id="reason"
                                    name="reason"
                                    rows="4"
                                    required
                                    maxlength="2000"
                                    class="mt-3 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                                >{{ old('reason') }}</textarea>

                                @error('reason')
                                    <p class="mt-2 text-sm font-medium text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror

                                <button
                                    type="submit"
                                    class="mt-4 inline-flex rounded-lg border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
                                >
                                    Approve Payment Bypass
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Organization Information
                </h2>

                <dl class="mt-6 grid gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">
                            Name
                        </dt>

                        <dd class="mt-2 text-sm font-semibold text-gray-900">
                            {{ data_get(
                                $organization,
                                'name',
                                '—'
                            ) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">
                            Slug
                        </dt>

                        <dd class="mt-2 text-sm text-gray-900">
                            {{ data_get(
                                $organization,
                                'slug',
                                '—'
                            ) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">
                            Email
                        </dt>

                        <dd class="mt-2 text-sm text-gray-900">
                            {{ data_get(
                                $organization,
                                'email',
                                '—'
                            ) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">
                            Created
                        </dt>

                        <dd class="mt-2 text-sm text-gray-900">
                            {{ data_get($organization, 'created_at')
                                ? data_get(
                                    $organization,
                                    'created_at'
                                )->format('M d, Y H:i')
                                : '—' }}
                        </dd>
                    </div>
                </dl>
            </section>
        </div>
    </div>
</x-app-layout>
