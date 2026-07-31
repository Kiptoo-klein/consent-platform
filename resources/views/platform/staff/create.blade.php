<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                Add Platform Staff
            </h1>

            <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                Create a Super Admin, Billing, Support, or Platform
                Auditor account.
            </p>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route(
                    'platform.staff.store'
                ) }}"
                class="space-y-6 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-8"
            >
                @csrf

                @if ($errors->any())
                    <div class="rounded-xl border border-red-300 bg-red-50 p-4 text-red-900">
                        <ul class="list-disc space-y-1 pl-5 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section
                    class="rounded-xl border-2 p-4 text-sm font-bold leading-6"
                    style="{{ $platformStaffLimitReached
                        ? 'background-color:#fef2f2;color:#7f1d1d;border-color:#dc2626;'
                        : 'background-color:#eff6ff;color:#1e3a8a;border-color:#3b82f6;' }}"
                >
                    <p class="font-extrabold">
                        Platform staff capacity:
                        {{ $platformStaffCount }} of
                        {{ $maximumPlatformStaff }} accounts used
                    </p>

                    @if ($platformStaffLimitReached)
                        <p class="mt-2">
                            Platform staff limit reached. A fifth account
                            cannot be created. Return to Platform Staff and
                            edit or reassign an existing account.
                        </p>
                    @else
                        <p class="mt-2">
                            {{ $maximumPlatformStaff
                                - $platformStaffCount }}
                            account slot remains.
                        </p>
                    @endif
                </section>

                <div>
                    <label
                        for="name"
                        class="block text-sm font-bold text-gray-900 dark:text-white"
                    >
                        Full name
                    </label>

                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label
                        for="email"
                        class="block text-sm font-bold text-gray-900 dark:text-white"
                    >
                        Email address
                    </label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label
                        for="platform_role_id"
                        class="block text-sm font-bold text-gray-900 dark:text-white"
                    >
                        Platform role
                    </label>

                    <select
                        id="platform_role_id"
                        name="platform_role_id"
                        required
                        class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                    >
                        <option value="">
                            Select a role
                        </option>

                        @foreach ($roles as $role)
                            <option
                                value="{{ $role->id }}"
                                @selected(
                                    (string) old(
                                        'platform_role_id'
                                    )
                                    === (string) $role->id
                                )
                            >
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="mt-3 space-y-2">
                        @foreach ($roles as $role)
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <strong>{{ $role->name }}:</strong>
                                {{ $role->description }}
                            </p>
                        @endforeach
                    </div>

                    @php
                        $superAdminRoleId =
                            $roles
                                ->firstWhere(
                                    'slug',
                                    'super-admin'
                                )
                                ?->id;
                    @endphp

                    <div
                        id="super-admin-role-warning"
                        data-super-admin-role-id="{{ $superAdminRoleId }}"
                        class="mt-4 rounded-xl border-2 p-4 text-sm font-bold leading-6"
                        style="background-color:#fef2f2 !important;color:#7f1d1d !important;border-color:#dc2626 !important;display:none;"
                        role="alert"
                    >
                        <p class="font-extrabold uppercase tracking-wide">
                            Warning: unrestricted platform access
                        </p>

                        <p class="mt-2">
                            Super Admin can access and modify all
                            organizations, billing records, payment
                            settings, security controls, subscription
                            plans, and platform staff accounts.
                        </p>

                        <p class="mt-2">
                            Assign this role only to fully trusted
                            personnel. The final active Super Admin cannot
                            be demoted or disabled.
                        </p>
                    </div>

                    <script>
                        document.addEventListener(
                            'DOMContentLoaded',
                            function () {
                                const roleSelect =
                                    document.getElementById(
                                        'platform_role_id'
                                    );

                                const warning =
                                    document.getElementById(
                                        'super-admin-role-warning'
                                    );

                                if (! roleSelect || ! warning) {
                                    return;
                                }

                                const updateWarning =
                                    function () {
                                        const superAdminRoleId =
                                            warning.dataset
                                                .superAdminRoleId;

                                        warning.style.display =
                                            roleSelect.value
                                            === superAdminRoleId
                                                ? 'block'
                                                : 'none';
                                    };

                                roleSelect.addEventListener(
                                    'change',
                                    updateWarning
                                );

                                updateWarning();
                            }
                        );
                    </script>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label
                            for="password"
                            class="block text-sm font-bold text-gray-900 dark:text-white"
                        >
                            Temporary password
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >
                    </div>

                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-sm font-bold text-gray-900 dark:text-white"
                        >
                            Confirm password
                        </label>

                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >
                    </div>
                </div>

                <p class="text-xs font-semibold leading-5 text-gray-600 dark:text-gray-400">
                    Passwords require at least eight characters with
                    uppercase, lowercase, number, and symbol characters.
                </p>

                <div class="flex flex-wrap justify-end gap-3">
                    <a
                        href="{{ route(
                            'platform.staff.index'
                        ) }}"
                        class="inline-flex rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-gray-800"
                    >
                        Cancel
                    </a>

                    @if ($platformStaffLimitReached)
                        <button
                            type="button"
                            disabled
                            class="inline-flex cursor-not-allowed rounded-xl border px-6 py-2.5 text-sm font-extrabold"
                            style="background-color:#e5e7eb !important;color:#6b7280 !important;border-color:#9ca3af !important;"
                        >
                            Maximum of Four Reached
                        </button>
                    @else
                        <button
                            type="submit"
                            class="inline-flex rounded-xl border px-6 py-2.5 text-sm font-extrabold shadow-sm"
                            style="background-color:#0f766e !important;color:#ffffff !important;border-color:#115e59 !important;"
                        >
                            Create Staff Account
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
