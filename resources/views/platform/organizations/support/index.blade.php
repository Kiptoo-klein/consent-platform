<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-teal-700">
                    Platform support
                </p>

                <h2 class="text-2xl font-bold text-gray-900">
                    {{ $organization->name }} support & recovery
                </h2>
            </div>

            <a
                href="{{ route(
                    'platform.organizations.show',
                    $organization
                ) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Back to organization
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h3 class="font-semibold text-amber-950">
                    Audited support access
                </h3>

                <p class="mt-2 text-sm leading-6 text-amber-900">
                    Use these tools only after the organization requests
                    assistance. Consent downloads and password-reset actions
                    require a support reason and are added to the audit log.
                    Completed consent evidence is never edited here.
                </p>
            </section>

            <div class="grid gap-5 md:grid-cols-3">
                <a
                    href="{{ route(
                        'platform.organizations.support.consent-records.index',
                        $organization
                    ) }}"
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-teal-300 hover:shadow-md"
                >
                    <p class="text-sm font-semibold text-teal-700">
                        Consent recovery
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format($consentRecordCount) }}
                    </p>

                    <p class="mt-2 text-sm text-gray-600">
                        {{ number_format($completedRecordCount) }}
                        completed records.
                    </p>
                </a>

                <a
                    href="{{ route(
                        'platform.organizations.users.index',
                        $organization
                    ) }}"
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-teal-300 hover:shadow-md"
                >
                    <p class="text-sm font-semibold text-teal-700">
                        User management
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format($users->count()) }}
                    </p>

                    <p class="mt-2 text-sm text-gray-600">
                        Edit roles, enable, archive, and restore users.
                    </p>
                </a>

                <a
                    href="{{ route(
                        'platform.organizations.users.create',
                        $organization
                    ) }}"
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-teal-300 hover:shadow-md"
                >
                    <p class="text-sm font-semibold text-teal-700">
                        Add organization user
                    </p>

                    <p class="mt-2 text-lg font-bold text-gray-900">
                        Create a new account
                    </p>

                    <p class="mt-2 text-sm text-gray-600">
                        Subscription seat and role limits still apply.
                    </p>
                </a>
            </div>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Account recovery
                    </h3>

                    <p class="mt-1 text-sm text-gray-600">
                        Send secure password-reset links. Existing
                        passwords are never displayed.
                    </p>
                </div>

                <div class="divide-y divide-gray-200">
                    @forelse ($users as $user)
                        <div class="grid gap-4 px-6 py-5 lg:grid-cols-[1fr_auto] lg:items-center">
                            <div>
                                <p class="font-semibold text-gray-900">
                                    {{ $user->name }}
                                </p>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $user->email }}
                                </p>

                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($user->roles as $role)
                                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-800">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach

                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                                        {{ $user->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>

                            <form
                                method="POST"
                                action="{{ route(
                                    'platform.organizations.support.users.password-reset',
                                    [
                                        $organization,
                                        $user,
                                    ]
                                ) }}"
                                class="flex w-full flex-col gap-2 sm:min-w-[360px] sm:flex-row"
                            >
                                @csrf

                                <input
                                    type="text"
                                    name="support_reason"
                                    required
                                    minlength="10"
                                    maxlength="1000"
                                    placeholder="Reason for account recovery"
                                    class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600"
                                >

                                <button
                                    type="submit"
                                    @disabled(! $user->is_active)
                                    class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Send reset link
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="px-6 py-10 text-center text-sm text-gray-500">
                            This organization has no active user accounts.
                        </p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
