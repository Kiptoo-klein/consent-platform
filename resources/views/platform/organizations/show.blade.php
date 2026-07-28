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

                        <span
                            class="inline-flex w-fit rounded-full border px-3 py-1 text-sm font-semibold
                                {{ $hasAccess
                                    ? 'border-green-200 bg-green-100 text-green-800'
                                    : 'border-amber-200 bg-amber-100 text-amber-900' }}"
                        >
                            {{ $hasAccess ? 'Access allowed' : 'Access blocked' }}
                        </span>
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
