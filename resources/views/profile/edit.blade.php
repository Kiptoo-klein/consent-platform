<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                Profile
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Manage your account information and password.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'profile-updated')
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    Profile updated successfully.
                </div>
            @endif

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Profile Information
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Update your name and email address.
                </p>

                <form
                    method="POST"
                    action="{{ route('profile.update') }}"
                    class="mt-6 space-y-5"
                >
                    @csrf
                    @method('PATCH')

                    <div>
                        <label
                            for="name"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Name
                        </label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            autocomplete="name"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >

                        @error('name')
                            <p class="mt-2 text-sm text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
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
                            value="{{ old('email', $user->email) }}"
                            required
                            autocomplete="email"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >

                        @error('email')
                            <p class="mt-2 text-sm text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
                    >
                        Save Profile
                    </button>
                </form>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Update Password
                </h2>

                <form
                    method="POST"
                    action="{{ route('password.update') }}"
                    class="mt-6 space-y-5"
                >
                    @csrf
                    @method('PUT')

                    <div>
                        <label
                            for="update_password_current_password"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Current Password
                        </label>

                        <input
                            id="update_password_current_password"
                            type="password"
                            name="current_password"
                            autocomplete="current-password"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >

                        @error('current_password', 'updatePassword')
                            <p class="mt-2 text-sm text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="update_password_password"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            New Password
                        </label>

                        <input
                            id="update_password_password"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >

                        @error('password', 'updatePassword')
                            <p class="mt-2 text-sm text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="update_password_password_confirmation"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Confirm New Password
                        </label>

                        <input
                            id="update_password_password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
                    >
                        Update Password
                    </button>
                </form>
            </section>

            <section class="rounded-2xl border border-red-200 bg-white p-6 shadow-sm">
                @if (
                    $isOrganizationAdministrator
                    && $organization !== null
                )
                    <h2 class="text-lg font-semibold text-red-800">
                        Archive Organization
                    </h2>

                    <p class="mt-2 text-sm text-gray-600">
                        As an Organization Admin, archiving from your
                        profile archives the entire organization rather
                        than only your personal account.
                    </p>

                    <div class="mt-4 rounded-xl border border-red-300 bg-red-50 p-4">
                        <p class="text-sm font-semibold text-red-900">
                            This affects every user in
                            {{ $organization->name }}.
                        </p>

                        <p class="mt-2 text-sm text-red-800">
                            All organization users will lose access.
                            Public signing stations and consent signing
                            workflows will become unavailable. Existing
                            records and historical data will remain
                            preserved.
                        </p>

                        <p class="mt-2 text-sm font-semibold text-red-900">
                            Only a Platform Super Admin will be able to
                            restore the organization.
                        </p>
                    </div>
                @else
                    <h2 class="text-lg font-semibold text-red-800">
                        Archive Account
                    </h2>

                    <p class="mt-2 text-sm text-gray-600">
                        Your account will be archived and you will be
                        logged out. Your organization's records and
                        other users will not be affected.
                    </p>

                    @if ($organization !== null)
                        <p class="mt-2 text-sm text-gray-600">
                            An Organization Admin can restore your
                            account later from Archived Users.
                        </p>
                    @endif

                    @if ($isBillingOwner)
                        <div class="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-4">
                            <p class="text-sm font-semibold text-amber-900">
                                Billing ownership must be transferred first.
                            </p>

                            <p class="mt-1 text-sm text-amber-800">
                                Ask an Organization Admin to assign another
                                Billing Owner before archiving this account.
                            </p>
                        </div>
                    @endif
                @endif

                <form
                    method="POST"
                    action="{{ route('profile.destroy') }}"
                    class="mt-6 space-y-5"
                    x-data
                    data-profile-archive-confirmation
                >
                    @csrf
                    @method('DELETE')

                    @error('archive', 'userDeletion')
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                            {{ $message }}
                        </div>
                    @enderror

                    <div>
                        <label
                            for="delete_password"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Confirm Password
                        </label>

                        <input
                            id="delete_password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-600 focus:ring-red-600"
                            x-ref="archivePassword"
                        >

                        @error('password', 'userDeletion')
                            <p class="mt-2 text-sm text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    @if (
                        $isOrganizationAdministrator
                        && $organization !== null
                    )
                        <div>
                            <label
                                for="organization_name"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Type the organization name to confirm
                            </label>

                            <p class="mt-1 text-sm text-gray-500">
                                Enter
                                <span class="font-semibold text-gray-800">
                                    {{ $organization->name }}
                                </span>
                                exactly as shown.
                            </p>

                            <input
                                id="organization_name"
                                type="text"
                                name="organization_name"
                                value="{{ old('organization_name') }}"
                                required
                                autocomplete="off"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-600 focus:ring-red-600"
                                x-ref="archiveOrganizationName"
                            >

                            @error(
                                'organization_name',
                                'userDeletion'
                            )
                                <p class="mt-2 text-sm text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endif

                    <button
                        type="button"
                        @disabled(
                            $isBillingOwner
                            && ! $isOrganizationAdministrator
                        )
                        class="inline-flex items-center rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                        x-on:click="
                            if (
                                $refs.archivePassword.reportValidity()
                                @if (
                                    $isOrganizationAdministrator
                                    && $organization !== null
                                )
                                    && $refs.archiveOrganizationName
                                        .reportValidity()
                                @endif
                            ) {
                                $dispatch(
                                    'open-modal',
                                    'archive-profile-{{ auth()->id() }}'
                                )
                            }
                        "
                    >
                        @if (
                            $isOrganizationAdministrator
                            && $organization !== null
                        )
                            Archive Organization
                        @else
                            Archive Account
                        @endif
                    </button>

                    <x-action-confirmation-modal
                        name="archive-profile-{{ auth()->id() }}"
                        :title="
                            $isOrganizationAdministrator
                            && $organization !== null
                                ? 'Archive '.$organization->name.'?'
                                : 'Archive your account?'
                        "
                        :message="
                            $isOrganizationAdministrator
                            && $organization !== null
                                ? 'Every user in this organization will lose access and public signing workflows will become unavailable. Existing records will remain preserved.'
                                : 'Your account will be archived immediately and you will be logged out. Your organization records will remain preserved.'
                        "
                        :confirm-text="
                            $isOrganizationAdministrator
                            && $organization !== null
                                ? 'Archive organization'
                                : 'Archive my account'
                        "
                        variant="danger"
                    />
                </form>
            </section>

        </div>
    </div>
</x-app-layout>
