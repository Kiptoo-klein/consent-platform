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
