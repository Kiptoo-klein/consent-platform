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
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                Add Organization User
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Invite a user to {{ $organization->name }}.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route(
                        $organizationUserRoutePrefix.'.store',
                        $organization
                    ) }}"
                    class="space-y-6"
                >
                    @csrf

                    <div>
                        <label
                            for="name"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Full Name
                        </label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            autocomplete="name"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >
                    </div>

                    <div>
                        <label
                            for="email"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Email Address
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >
                    </div>

                    <div>
                        <label
                            for="role_id"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Organization Role
                        </label>

                        {{-- ORGANIZATION_ADMIN_ROLE_WARNING --}}
                        @php
                            $orderedRoles = $roles
                                ->sortBy(
                                    fn ($role) =>
                                        $role->name ===
                                        'Organization Admin'
                                            ? 1
                                            : 0
                                )
                                ->values();

                            $organizationAdminRoleId =
                                (string) (
                                    $orderedRoles
                                        ->firstWhere(
                                            'name',
                                            'Organization Admin'
                                        )
                                        ?->id ?? ''
                                );

                            $selectedRoleId =
                                (string) old('role_id');

                            $orderedRoleGuide = collect(
                                \App\Enums\OrganizationRole::ordered()
                            )
                                ->sortBy(
                                    fn ($roleDetails) =>
                                        $roleDetails ===
                                        \App\Enums\OrganizationRole::
                                            ORGANIZATION_ADMINISTRATOR
                                            ? 1
                                            : 0
                                )
                                ->values();
                        @endphp

                        <div
                            x-data="{
                                selectedRoleId:
                                    @js($selectedRoleId),

                                organizationAdminRoleId:
                                    @js($organizationAdminRoleId),
                            }"
                        >
                            <select
                                id="role_id"
                                name="role_id"
                                required
                                x-model="selectedRoleId"
                                class="mt-2 block w-full rounded-lg shadow-sm"
                                :class="
                                    selectedRoleId ===
                                        organizationAdminRoleId
                                        ? 'border-red-400 text-red-700 focus:border-red-500 focus:ring-red-500'
                                        : 'border-gray-300 text-gray-900 focus:border-teal-700 focus:ring-teal-700'
                                "
                            >
                                <option value="">
                                    Select a role
                                </option>

                                @foreach ($orderedRoles as $role)
                                    <option
                                        value="{{ $role->id }}"
                                        @selected(
                                            $selectedRoleId ===
                                            (string) $role->id
                                        )
                                        @class([
                                            'font-semibold text-red-700' =>
                                                $role->name ===
                                                'Organization Admin',
                                        ])
                                    >
                                        {{ $role->name ===
                                            'Organization Admin'
                                                ? 'Organization Admin — Full access (use with caution)'
                                                : $role->name }}
                                    </option>
                                @endforeach
                            </select>

                            <div
                                x-cloak
                                x-show="
                                    organizationAdminRoleId !== ''
                                    && selectedRoleId ===
                                        organizationAdminRoleId
                                "
                                role="alert"
                                aria-live="polite"
                                class="mt-4 rounded-xl border-2 border-red-300 bg-red-50 p-4 text-red-900"
                            >
                                <p class="font-bold">
                                    Warning: Organization Admin grants
                                    full organizational control.
                                </p>

                                <p class="mt-2 text-sm leading-6">
                                    This user can manage users, roles,
                                    subscription access, branding, settings,
                                    and organizational workflows. Assign this
                                    role only to a highly trusted person.
                                    Assigning this role does not automatically
                                    change the current billing owner.
                                </p>
                            </div>
                        </div>

                        {{-- ORGANIZATION_ROLE_GUIDE --}}
                        @if ($roles->isNotEmpty())
                            <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-sm font-semibold text-gray-900">
                                    Role guide
                                </p>

                                <p class="mt-1 text-xs leading-5 text-gray-500">
                                    Higher roles include the access provided by
                                    the roles below them.
                                </p>

                                <div class="mt-4 space-y-3">
                                    @foreach (
                                        $orderedRoleGuide
                                        as $roleDetails
                                    )
                                        <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                                            <p class="text-sm font-semibold text-gray-900">
                                                {{ $roleDetails->label() }}
                                            </p>

                                            <p class="mt-1 text-xs font-medium text-teal-700">
                                                Example:
                                                {{ $roleDetails->example() }}
                                            </p>

                                            <p class="mt-2 text-sm leading-6 text-gray-600">
                                                {{ $roleDetails->description() }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($roles->isEmpty())
                            <p class="mt-2 text-sm text-red-700">
                                No organization roles are currently available.
                            </p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-teal-200 bg-teal-50 p-4">
                        <p class="text-sm font-semibold text-teal-900">
                            The user will create their own password
                        </p>

                        <p class="mt-1 text-sm leading-6 text-teal-800">
                            eConsent will email this person their name,
                            role, email address, and a secure account-setup
                            link. Administrators never create or receive
                            the user's password.
                        </p>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-gray-200 pt-6">
                        <a
                            href="{{ route(
                                $organizationUserRoutePrefix.'.index',
                                $organization
                            ) }}"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            @disabled($roles->isEmpty())
                            class="inline-flex items-center rounded-lg border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Send Invitation
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
