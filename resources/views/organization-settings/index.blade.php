<x-app-layout>
    <x-slot name="header">
        <div>
            <h1
                class="text-2xl font-bold text-gray-900 dark:text-white"
            >
                Settings
            </h1>

            <p
                class="mt-1 text-sm text-gray-600 dark:text-gray-400"
            >
                Manage your organization, access, subscription and billing settings.
            </p>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div
            class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
        >
            <div
                class="grid gap-6 lg:grid-cols-2"
            >
                @if ($isOrganizationAdministrator)
                    <section
                        class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400"
                            >
                                Organization
                            </p>

                            <h2
                                class="mt-2 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                Team & Access
                            </h2>

                            <p
                                class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                            >
                                Manage organization users, roles and access.
                            </p>
                        </div>

                        <div class="mt-6">
                            <a
                                href="{{ route(
                                    'organization-users.index',
                                    $organization
                                ) }}"
                                class="inline-flex items-center text-sm font-semibold text-indigo-700 transition hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                Manage Users
                                <span
                                    class="ml-2"
                                    aria-hidden="true"
                                >
                                    →
                                </span>
                            </a>
                        </div>
                    </section>

                    <section
                        class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400"
                            >
                                Organization
                            </p>

                            <h2
                                class="mt-2 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                Organization Branding
                            </h2>

                            <p
                                class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                            >
                                Manage how your organization appears across consent forms and signing stations.
                            </p>
                        </div>

                        <div class="mt-6">
                            <a
                                href="{{ route(
                                    'organization-branding.edit'
                                ) }}"
                                class="inline-flex items-center text-sm font-semibold text-indigo-700 transition hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                Manage Branding
                                <span
                                    class="ml-2"
                                    aria-hidden="true"
                                >
                                    →
                                </span>
                            </a>
                        </div>
                    </section>
                @endif

                <section
                    class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900
                        {{ $isOrganizationAdministrator
                            ? 'lg:col-span-2'
                            : '' }}"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400"
                        >
                            Subscription
                        </p>

                        <h2
                            class="mt-2 text-xl font-bold text-gray-900 dark:text-white"
                        >
                            Subscription & Billing
                        </h2>

                        <p
                            class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                        >
                            Review your plan, subscription usage and the billing options available to your account.
                        </p>
                    </div>

                    <div
                        class="mt-6 grid gap-4 md:grid-cols-3"
                    >
                        <a
                            href="{{ route(
                                'organization-subscription.show'
                            ) }}"
                            class="rounded-2xl border border-gray-200 p-5 transition hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-gray-700 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/20"
                        >
                            <h3
                                class="font-semibold text-gray-900 dark:text-white"
                            >
                                Subscription & Usage
                            </h3>

                            <p
                                class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                            >
                                View your current plan, access status and usage against plan limits.
                            </p>
                        </a>

                        @if (
                            $isBillingOwner
                            || $isOrganizationAdministrator
                        )
                            <a
                                href="{{ route(
                                    'organization-subscription-plans.index'
                                ) }}"
                                class="rounded-2xl border border-gray-200 p-5 transition hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-gray-700 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/20"
                            >
                                <h3
                                    class="font-semibold text-gray-900 dark:text-white"
                                >
                                    Subscription Plans
                                </h3>

                                <p
                                    class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                                >
                                    Review available plans and manage plan requests.
                                </p>
                            </a>

                            <a
                                href="{{ route(
                                    'organization-billing.index'
                                ) }}"
                                class="rounded-2xl border border-gray-200 p-5 transition hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-gray-700 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/20"
                            >
                                <h3
                                    class="font-semibold text-gray-900 dark:text-white"
                                >
                                    Billing & Receipts
                                </h3>

                                <p
                                    class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                                >
                                    Review invoices, receipts, transactions and billing settings.
                                </p>
                            </a>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
