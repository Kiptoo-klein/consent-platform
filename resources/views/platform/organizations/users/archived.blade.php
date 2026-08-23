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
                    Archived Users
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Archived accounts belonging to
                    {{ $organization->name }}.
                </p>
            </div>

            <a
                href="{{ route(
                    $organizationUserRoutePrefix.'.index',
                    $organization
                ) }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Back to Active Users
            </a>
        </div>
    </x-slot>

    @php
        $archivedUsers = $users ?? collect();
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

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Archived Accounts
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Restoring an account also enables it immediately,
                        allowing the user to sign in again according to their
                        assigned role.
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
                                    Archived
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($archivedUsers as $user)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ $user->name }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            User ID: {{ $user->id }}
                                        </p>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $user->email }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $user->roles->first()?->name
                                            ?? 'No role assigned' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                        {{ $user->deleted_at
                                            ? $user->deleted_at->copy()->timezone(config('app.display_timezone'))->format(
                                                'M d, Y H:i'
                                            )
                                            : '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                $organizationUserRoutePrefix.'.restore',
                                                [$organization, $user]
                                            ) }}"
                                            x-data
                                            data-user-restore-confirmation
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-lg border border-green-300 bg-green-50 px-4 py-2 text-sm font-semibold text-green-800 transition hover:bg-green-100"
                                                x-on:click="$dispatch(
                                                    'open-modal',
                                                    'restore-user-{{ $organization->id }}-{{ $user->id }}'
                                                )"
                                            >
                                                Restore &amp; Enable
                                            </button>

                                            <x-action-confirmation-modal
                                                name="restore-user-{{ $organization->id }}-{{ $user->id }}"
                                                title="Restore and enable user?"
                                                message="The account will be restored, enabled immediately, and will regain access according to its assigned role."
                                                confirm-text="Restore & Enable"
                                                variant="success"
                                            />
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="5"
                                        class="px-6 py-14 text-center"
                                    >
                                        <p class="font-semibold text-gray-800">
                                            No archived users
                                        </p>

                                        <p class="mt-2 text-sm text-gray-500">
                                            This organization has no archived
                                            user accounts.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (
                    is_object($archivedUsers)
                    && method_exists($archivedUsers, 'hasPages')
                    && $archivedUsers->hasPages()
                )
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $archivedUsers->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
