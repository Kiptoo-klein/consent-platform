<x-app-layout>
    @php
        $organizationUserRoutePrefix =
            request()->routeIs('platform.*')
                ? 'platform.organizations.users'
                : 'organization-users';

        $organizationUserIsPlatformContext =
            request()->routeIs('platform.*');
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Manage Users
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Users belonging to {{ $organization->name }}.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route(
                        $organizationUserRoutePrefix.'.create',
                        $organization
                    ) }}"
                    class="inline-flex items-center rounded-lg border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
                >
                    Add User
                </a>

                @if (\Illuminate\Support\Facades\Route::has(
                    $organizationUserRoutePrefix.'.archived'
                ))
                    <a
                        href="{{ route(
                            $organizationUserRoutePrefix.'.archived',
                            $organization
                        ) }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Archived Users
                    </a>
                @endif

                <a
                    href="{{ $organizationUserIsPlatformContext
                            ? route(
                                'platform.organizations.show',
                                $organization
                            )
                            : route('dashboard') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Back to Organization
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $userList = $users ?? collect();

        $totalUsers = is_object($userList)
            && method_exists($userList, 'total')
                ? $userList->total()
                : count($userList);
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Organization
                    </p>

                    <p class="mt-2 text-lg font-bold text-gray-900">
                        {{ $organization->name }}
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Total Users
                    </p>

                    <p class="mt-2 text-3xl font-bold text-teal-700">
                        {{ number_format($totalUsers) }}
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Organization ID
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $organization->id }}
                    </p>
                </section>
            </div>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Organization Users
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Create, edit, enable, disable, and archive users.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    User
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Email
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Role
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Created
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($userList as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-teal-100 font-bold text-teal-800">
                                                {{ strtoupper(
                                                    substr(
                                                        $user->name ?: '?',
                                                        0,
                                                        1
                                                    )
                                                ) }}
                                            </div>

                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">
                                                    {{ $user->name }}
                                                </p>

                                                <p class="mt-1 text-xs text-gray-500">
                                                    User ID: {{ $user->id }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $user->email }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $user->roles->first()?->name
                                            ?? 'No role assigned' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($user->is_active)
                                            <span class="inline-flex rounded-full border border-green-200 bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full border border-red-200 bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">
                                                Disabled
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                        {{ $user->created_at
                                            ? $user->created_at->copy()->timezone(config('app.display_timezone'))->format(
                                                'M d, Y'
                                            )
                                            : '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <a
                                                href="{{ route(
                                                    $organizationUserRoutePrefix.'.edit',
                                                    [$organization, $user]
                                                ) }}"
                                                class="inline-flex items-center rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 transition hover:bg-amber-100"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    $organizationUserRoutePrefix.'.status',
                                                    [$organization, $user]
                                                ) }}"
                                                x-data
                                                data-user-status-confirmation
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <input
                                                    type="hidden"
                                                    name="is_active"
                                                    value="{{ $user->is_active
                                                        ? 0
                                                        : 1 }}"
                                                >

                                                <button
                                                    type="button"
                                                    class="inline-flex items-center rounded-lg border px-3 py-2 text-xs font-semibold transition {{ $user->is_active
                                                        ? 'border-red-200 bg-red-50 text-red-800 hover:bg-red-100'
                                                        : 'border-green-200 bg-green-50 text-green-800 hover:bg-green-100' }}"
                                                    x-on:click="$dispatch(
                                                        'open-modal',
                                                        'user-status-{{ $organization->id }}-{{ $user->id }}'
                                                    )"
                                                >
                                                    {{ $user->is_active
                                                        ? 'Disable'
                                                        : 'Enable' }}
                                                </button>

                                                <x-action-confirmation-modal
                                                    name="user-status-{{ $organization->id }}-{{ $user->id }}"
                                                    :title="$user->is_active
                                                        ? 'Disable user account?'
                                                        : 'Enable user account?'"
                                                    :message="$user->is_active
                                                        ? 'This user will no longer be able to access the organization until the account is enabled again.'
                                                        : 'This user will regain access according to their assigned organization role.'"
                                                    :confirm-text="$user->is_active
                                                        ? 'Disable user'
                                                        : 'Enable user'"
                                                    :variant="$user->is_active
                                                        ? 'danger'
                                                        : 'success'"
                                                />
                                            </form>

                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    $organizationUserRoutePrefix.'.destroy',
                                                    [$organization, $user]
                                                ) }}"
                                                x-data
                                                data-user-archive-confirmation
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="button"
                                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-100"
                                                    x-on:click="$dispatch(
                                                        'open-modal',
                                                        'archive-user-{{ $organization->id }}-{{ $user->id }}'
                                                    )"
                                                >
                                                    Archive
                                                </button>

                                                <x-action-confirmation-modal
                                                    name="archive-user-{{ $organization->id }}-{{ $user->id }}"
                                                    title="Archive user account?"
                                                    message="This user will lose access to the organization. The account and its history will be preserved and can be restored later."
                                                    confirm-text="Archive user"
                                                    variant="danger"
                                                />
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-6 py-14 text-center"
                                    >
                                        <p class="font-semibold text-gray-800">
                                            No users found
                                        </p>

                                        <p class="mt-2 text-sm text-gray-500">
                                            Add the first user for this
                                            organization.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (
                    is_object($userList)
                    && method_exists($userList, 'hasPages')
                    && $userList->hasPages()
                )
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $userList->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
