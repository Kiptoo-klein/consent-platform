<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Organizations
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    View and manage organizations registered on the platform.
                </p>
            </div>

            <a
                href="{{ route('platform.dashboard') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    @php
        $organizationList = $organizations
            ?? collect();
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Registered Organizations
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Select an organization to view its details and users.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Organization
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Status
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Created
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($organizationList as $organization)
                                @php
                                    $active = data_get(
                                        $organization,
                                        'is_active',
                                        true
                                    );
                                @endphp

                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900">
                                            {{ data_get(
                                                $organization,
                                                'name',
                                                'Unnamed organization'
                                            ) }}
                                        </div>

                                        <div class="mt-1 text-sm text-gray-500">
                                            ID: {{ data_get(
                                                $organization,
                                                'id',
                                                '—'
                                            ) }}

                                            @if (data_get(
                                                $organization,
                                                'slug'
                                            ))
                                                · {{ data_get(
                                                    $organization,
                                                    'slug'
                                                ) }}
                                            @endif
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if ($active)
                                            <span class="inline-flex rounded-full border border-green-200 bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full border border-red-200 bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">
                                                Disabled
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        {{ data_get($organization, 'created_at')
                                            ? data_get(
                                                $organization,
                                                'created_at'
                                            )->copy()->timezone(config('app.display_timezone'))->format('M d, Y')
                                            : '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <a
                                            href="{{ route(
                                                'platform.organizations.show',
                                                $organization
                                            ) }}"
                                            class="inline-flex items-center rounded-lg border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
                                        >
                                            View Organization
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-6 py-14 text-center"
                                    >
                                        <p class="font-semibold text-gray-800">
                                            No organizations found
                                        </p>

                                        <p class="mt-2 text-sm text-gray-500">
                                            There are currently no organizations
                                            available to display.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (
                    is_object($organizationList)
                    && method_exists($organizationList, 'hasPages')
                    && $organizationList->hasPages()
                )
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $organizationList->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
