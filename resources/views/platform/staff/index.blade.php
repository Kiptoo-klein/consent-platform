<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                    Platform Staff
                </h1>

                <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Manage Super Admin, Billing, Support, and Platform
                    Auditor accounts.
                </p>
            </div>

            @if (
                $platformStaffCount
                < $maximumPlatformStaff
            )
                <a
                    href="{{ route(
                        'platform.staff.create'
                    ) }}"
                    class="inline-flex w-fit items-center justify-center rounded-xl border px-5 py-2.5 text-sm font-extrabold shadow-sm transition hover:opacity-90"
                    style="background-color:#0f766e !important;color:#ffffff !important;border-color:#115e59 !important;"
                >
                    Add Platform Staff
                </a>
            @else
                <span
                    class="inline-flex w-fit items-center justify-center rounded-xl border px-5 py-2.5 text-sm font-extrabold"
                    style="background-color:#e5e7eb !important;color:#374151 !important;border-color:#9ca3af !important;"
                >
                    Maximum reached
                </span>
            @endif
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-green-300 bg-green-50 px-5 py-4 text-sm font-bold text-green-900">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-300 bg-red-50 px-5 py-4 text-red-900">
                    <p class="font-extrabold">
                        The staff account could not be changed.
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section
                class="rounded-2xl border-2 p-5 shadow-sm"
                style="{{ $platformStaffCount >= $maximumPlatformStaff
                    ? 'background-color:#fef2f2;border-color:#dc2626;color:#7f1d1d;'
                    : 'background-color:#eff6ff;border-color:#3b82f6;color:#1e3a8a;' }}"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-extrabold">
                            Platform staff capacity
                        </h2>

                        <p class="mt-1 text-sm font-semibold">
                            {{ $platformStaffCount }} of
                            {{ $maximumPlatformStaff }} accounts used
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-extrabold"
                        style="background-color:#ffffff;color:#111827;"
                    >
                        {{ max(
                            0,
                            $maximumPlatformStaff
                            - $platformStaffCount
                        ) }}
                        remaining
                    </span>
                </div>

                @if (
                    $platformStaffCount
                    >= $maximumPlatformStaff
                )
                    <p class="mt-4 text-sm font-extrabold leading-6">
                        Platform staff limit reached. Edit or reassign one
                        of the existing four accounts instead of creating
                        another.
                    </p>
                @else
                    <p class="mt-4 text-sm font-semibold leading-6">
                        The platform supports a maximum of four staff
                        accounts across Super Admin, Billing, Support, and
                        Platform Auditor roles.
                    </p>
                @endif
            </section>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($roles as $role)
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-sm font-extrabold text-gray-950 dark:text-white">
                            {{ $role->name }}
                        </p>

                        <p class="mt-2 text-3xl font-extrabold text-indigo-700 dark:text-indigo-300">
                            {{ number_format(
                                $role->users_count
                            ) }}
                        </p>

                        <p class="mt-1 text-xs font-semibold text-gray-600 dark:text-gray-400">
                            Active accounts
                        </p>

                        <p class="mt-3 text-xs leading-5 text-gray-600 dark:text-gray-400">
                            {{ $role->description }}
                        </p>
                    </div>
                @endforeach
            </section>

            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                    <h2 class="text-lg font-extrabold text-gray-950 dark:text-white">
                        Staff accounts
                    </h2>

                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">
                        Only active Super Admin accounts can manage this list.
                    </p>
                </div>

                @if ($staff->isEmpty())
                    <div class="p-8 text-center">
                        <p class="font-bold text-gray-950 dark:text-white">
                            No platform staff accounts found.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-950">
                                <tr>
                                    @foreach ([
                                        'Staff member',
                                        'Role',
                                        'Status',
                                        'Actions',
                                    ] as $heading)
                                        <th class="px-6 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @foreach ($staff as $staffMember)
                                    <tr class="align-top">
                                        <td class="px-6 py-4">
                                            <p class="font-extrabold text-gray-950 dark:text-white">
                                                {{ $staffMember->name }}
                                            </p>

                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $staffMember->email }}
                                            </p>

                                            @if (
                                                auth()->user()->is(
                                                    $staffMember
                                                )
                                            )
                                                <span class="mt-2 inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-bold text-indigo-800">
                                                    Your account
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-extrabold text-blue-900">
                                                {{ $staffMember
                                                    ->platformRole
                                                    ?->name
                                                    ?? 'No role' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            @if ($staffMember->is_active)
                                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-extrabold text-green-900">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-extrabold text-red-900">
                                                    Disabled
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-2">
                                                <a
                                                    href="{{ route(
                                                        'platform.staff.edit',
                                                        $staffMember
                                                    ) }}"
                                                    class="inline-flex rounded-lg border px-3 py-2 text-xs font-extrabold"
                                                    style="background-color:#1d4ed8 !important;color:#ffffff !important;border-color:#1e40af !important;"
                                                >
                                                    Edit
                                                </a>

                                                @unless (
                                                    auth()->user()->is(
                                                        $staffMember
                                                    )
                                                )
                                                    <form
                                                        method="POST"
                                                        action="{{ route(
                                                            'platform.staff.status',
                                                            $staffMember
                                                        ) }}"
                                                        x-data
                                                        data-platform-staff-status-confirmation
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <input
                                                            type="hidden"
                                                            name="is_active"
                                                            value="{{ $staffMember->is_active
                                                                ? '0'
                                                                : '1' }}"
                                                        >

                                                        <button
                                                            type="button"
                                                            class="inline-flex rounded-lg border px-3 py-2 text-xs font-extrabold"
                                                            style="{{ $staffMember->is_active
                                                                ? 'background-color:#b91c1c !important;color:#ffffff !important;border-color:#991b1b !important;'
                                                                : 'background-color:#15803d !important;color:#ffffff !important;border-color:#166534 !important;' }}"
                                                            x-on:click="$dispatch(
                                                                'open-modal',
                                                                'platform-staff-status-{{ $staffMember->id }}'
                                                            )"
                                                        >
                                                            {{ $staffMember->is_active
                                                                ? 'Disable'
                                                                : 'Enable' }}
                                                        </button>

                                                        <x-action-confirmation-modal
                                                            name="platform-staff-status-{{ $staffMember->id }}"
                                                            :title="$staffMember->is_active
                                                                ? 'Disable platform staff account?'
                                                                : 'Enable platform staff account?'"
                                                            :message="$staffMember->is_active
                                                                ? 'This staff member will lose access to platform administration until the account is enabled again.'
                                                                : 'This staff member will regain access to platform administration.'"
                                                            :confirm-text="$staffMember->is_active
                                                                ? 'Disable staff account'
                                                                : 'Enable staff account'"
                                                            :variant="$staffMember->is_active
                                                                ? 'danger'
                                                                : 'success'"
                                                        />
                                                    </form>
                                                @endunless
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-800">
                        {{ $staff->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
