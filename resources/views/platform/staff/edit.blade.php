<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                Edit Platform Staff
            </h1>

            <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                Update {{ $platformStaff->name }} and assign the correct
                platform role.
            </p>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route(
                    'platform.staff.update',
                    $platformStaff
                ) }}"
                class="space-y-6 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-8"
            >
                @csrf
                @method('PATCH')

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

                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                    <p class="text-sm font-bold text-gray-950 dark:text-white">
                        Account status:
                        {{ $platformStaff->is_active
                            ? 'Active'
                            : 'Disabled' }}
                    </p>
                </div>

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
                        value="{{ old(
                            'name',
                            $platformStaff->name
                        ) }}"
                        required
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
                        value="{{ old(
                            'email',
                            $platformStaff->email
                        ) }}"
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
                        @foreach ($roles as $role)
                            <option
                                value="{{ $role->id }}"
                                @selected(
                                    (string) old(
                                        'platform_role_id',
                                        $platformStaff
                                            ->platform_role_id
                                    )
                                    === (string) $role->id
                                )
                            >
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
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

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label
                            for="password"
                            class="block text-sm font-bold text-gray-900 dark:text-white"
                        >
                            New password
                        </label>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >
                    </div>

                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-sm font-bold text-gray-900 dark:text-white"
                        >
                            Confirm new password
                        </label>

                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-600 focus:ring-indigo-600 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >
                    </div>
                </div>

                <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                    Leave both password fields blank to keep the current
                    password.
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

                    <button
                        type="submit"
                        class="inline-flex rounded-xl border px-6 py-2.5 text-sm font-extrabold shadow-sm"
                        style="background-color:#1d4ed8 !important;color:#ffffff !important;border-color:#1e40af !important;"
                    >
                        Save Staff Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
