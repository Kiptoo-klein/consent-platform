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
            @if ($requiresSubscriptionRecovery)
                <section
                    data-subscription-recovery
                    class="rounded-3xl border border-amber-300 bg-amber-50 p-6 shadow-sm dark:border-amber-800 dark:bg-amber-950/30 sm:p-8"
                >
                    <div
                        class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div class="max-w-3xl">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400"
                            >
                                Subscription
                            </p>

                            <h2
                                class="mt-2 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                Subscription access needs attention
                            </h2>

                            @if (
                                $isBillingOwner
                                || $isOrganizationAdministrator
                            )
                                <p
                                    class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-300"
                                >
                                    Your organization's subscription is not currently providing workflow access. Review the subscription or select a plan to restore full access.
                                </p>
                            @else
                                <p
                                    class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-300"
                                >
                                    Your organization's subscription is not currently providing workflow access. Contact your Billing Owner or Organization Admin to restore full access.
                                </p>
                            @endif
                        </div>

                        <div
                            class="flex shrink-0 flex-wrap gap-3"
                        >
                            <a
                                href="{{ route(
                                    'organization-subscription.show'
                                ) }}"
                                class="inline-flex items-center justify-center rounded-xl border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-amber-900 transition hover:bg-amber-100 dark:border-amber-700 dark:bg-gray-900 dark:text-amber-300 dark:hover:bg-amber-950"
                            >
                                View Subscription
                            </a>

                            @if (
                                $isBillingOwner
                                || $isOrganizationAdministrator
                            )
                                <a
                                    href="{{ route(
                                        'organization-subscription-plans.index'
                                    ) }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700"
                                >
                                    View Plans
                                </a>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            @if ($evaluationOnboarding !== null)
                @php
                    $evaluationProgress =
                        $evaluationOnboarding['total_steps'] > 0
                            ? (int) round(
                                (
                                    $evaluationOnboarding['completed_steps']
                                    / $evaluationOnboarding['total_steps']
                                ) * 100
                            )
                            : 0;

                    $evaluationSteps = [
                        [
                            'key' => 'profile',
                            'title' =>
                                'Complete organization profile',
                            'description' =>
                                $isOrganizationAdministrator
                                    ? 'Add your organization email, phone number and address so your workspace is ready to use.'
                                    : 'An Organization Administrator needs to complete the organization profile for your workspace.',
                            'route' =>
                                $isOrganizationAdministrator
                                    ? route(
                                        'organization-branding.edit'
                                    )
                                    : null,
                            'action' =>
                                $isOrganizationAdministrator
                                    ? 'Complete profile'
                                    : null,
                        ],
                        [
                            'key' => 'starter_reviewed',
                            'title' =>
                                'Review a starter template',
                            'description' =>
                                'Open one of your starter templates and save your first changes.',
                            'route' =>
                                route(
                                    'consent-templates.manage'
                                ),
                            'action' =>
                                'Review templates',
                        ],
                        [
                            'key' => 'template_published',
                            'title' =>
                                'Publish your first template',
                            'description' =>
                                'Publish a working template so it becomes available for consent workflows.',
                            'route' =>
                                route(
                                    'consent-templates.manage'
                                ),
                            'action' =>
                                'Open templates',
                        ],
                        [
                            'key' => 'first_consent',
                            'title' =>
                                'Complete your first consent',
                            'description' =>
                                'Send yourself a test consent or complete one through a signing station.',
                            'route' =>
                                route(
                                    'consent-sessions.select-template',
                                    [
                                        'self_test' => 1,
                                    ]
                                ),
                            'action' =>
                                'Send test consent',
                        ],
                        [
                            'key' => 'signing_station',
                            'title' =>
                                'Create your first signing station',
                            'description' =>
                                'Create a kiosk or QR signing station using one of your published templates.',
                            'route' =>
                                route(
                                    'signing-stations.create'
                                ),
                            'action' =>
                                'Create station',
                        ],
                    ];

                    $evaluationNextStep = collect(
                        $evaluationSteps
                    )->first(
                        fn (array $step): bool =>
                            ! (
                                $evaluationOnboarding[
                                    'checklist'
                                ][
                                    $step['key']
                                ] ?? false
                            )
                    );
                @endphp

                <section
                    data-evaluation-settings
                    data-evaluation-settings-progress="{{ $evaluationOnboarding['completed_steps'] }}/{{ $evaluationOnboarding['total_steps'] }}"
                    data-evaluation-settings-complete="{{ $evaluationOnboarding['complete'] ? '1' : '0' }}"
                    class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
                >
                    <div
                        class="p-6 sm:p-8"
                    >
                        <div
                            class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between"
                        >
                            <div class="max-w-3xl">
                                <div
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <span
                                        class="inline-flex rounded-full bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-teal-700 ring-1 ring-inset ring-teal-200 dark:bg-teal-950/40 dark:text-teal-300 dark:ring-teal-900"
                                    >
                                        Free Evaluation
                                    </span>

                                    <span
                                        class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                    >
                                        No time limit
                                    </span>
                                </div>

                                <h2
                                    class="mt-4 text-xl font-bold text-gray-900 dark:text-white sm:text-2xl"
                                >
                                    Complete your evaluation setup
                                </h2>

                                <p
                                    class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-400"
                                >
                                    Try the core consent workflow and finish the guided setup before deciding whether to move to a paid plan.
                                </p>
                            </div>

                            <a
                                href="{{ route('dashboard') }}#evaluation-getting-started"
                                class="inline-flex shrink-0 items-center justify-center rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                Continue setup
                            </a>
                        </div>

                        <div class="mt-7">
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <p
                                    class="text-sm font-semibold text-gray-800 dark:text-gray-200"
                                >
                                    {{ $evaluationOnboarding['completed_steps'] }}
                                    of
                                    {{ $evaluationOnboarding['total_steps'] }}
                                    steps complete
                                </p>

                                <p
                                    class="text-sm font-semibold text-teal-700 dark:text-teal-300"
                                >
                                    {{ $evaluationProgress }}%
                                </p>
                            </div>

                            <div
                                class="mt-3 h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"
                                role="progressbar"
                                aria-label="Evaluation setup progress"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ $evaluationProgress }}"
                            >
                                <div
                                    data-evaluation-progress-bar
                                    class="h-full rounded-full bg-teal-600 transition-all"
                                    style="width: {{ $evaluationProgress }}%;"
                                ></div>
                            </div>
                        </div>

                        @if ($evaluationNextStep !== null)
                            <div
                                data-evaluation-next-step="{{ $evaluationNextStep['key'] }}"
                                class="mt-7 rounded-2xl border border-teal-100 bg-teal-50/70 p-5 dark:border-teal-900 dark:bg-teal-950/20"
                            >
                                <div
                                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="max-w-3xl">
                                        <p
                                            class="text-xs font-bold uppercase tracking-wide text-teal-700 dark:text-teal-300"
                                        >
                                            Next recommended step
                                        </p>

                                        <h3
                                            class="mt-1 text-base font-bold text-gray-900 dark:text-white"
                                        >
                                            {{ $evaluationNextStep['title'] }}
                                        </h3>

                                        <p
                                            class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400"
                                        >
                                            {{ $evaluationNextStep['description'] }}
                                        </p>
                                    </div>

                                    @if ($evaluationNextStep['route'] !== null)
                                        <a
                                            href="{{ $evaluationNextStep['route'] }}"
                                            class="inline-flex shrink-0 items-center justify-center rounded-xl border border-teal-200 bg-white px-4 py-2.5 text-sm font-semibold text-teal-700 transition hover:border-teal-300 hover:bg-teal-100 dark:border-teal-800 dark:bg-gray-900 dark:text-teal-300 dark:hover:bg-teal-950/40"
                                        >
                                            {{ $evaluationNextStep['action'] }}
                                            <span
                                                class="ml-2"
                                                aria-hidden="true"
                                            >
                                                →
                                            </span>
                                        </a>
                                    @else
                                        <a
                                            href="{{ route('dashboard') }}#evaluation-getting-started"
                                            class="inline-flex shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-teal-800 dark:hover:bg-teal-950/30 dark:hover:text-teal-300"
                                        >
                                            Continue setup
                                            <span
                                                class="ml-2"
                                                aria-hidden="true"
                                            >
                                                →
                                            </span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div
                                data-evaluation-setup-complete
                                class="mt-7 rounded-2xl border border-green-200 bg-green-50 p-5 dark:border-green-900 dark:bg-green-950/20"
                            >
                                <p
                                    class="text-sm font-bold text-green-800 dark:text-green-300"
                                >
                                    Evaluation setup complete
                                </p>

                                <p
                                    class="mt-1 text-sm leading-6 text-green-700 dark:text-green-400"
                                >
                                    You've completed every Getting Started step. You can keep exploring your evaluation workspace or review the available plans.
                                </p>
                            </div>
                        @endif
                    </div>

                    <div
                        class="grid border-t border-gray-200 bg-gray-50 sm:grid-cols-2 lg:grid-cols-4 dark:border-gray-700 dark:bg-gray-950/40"
                    >
                        <div
                            class="border-b border-gray-200 p-5 sm:border-r lg:border-b-0 dark:border-gray-700"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Invitation emails
                            </p>

                            <p
                                class="mt-2 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ $evaluationOnboarding['usage']['emails']['used'] }}
                                <span
                                    class="text-sm font-medium text-gray-400"
                                >
                                    /
                                    {{ $evaluationOnboarding['usage']['emails']['limit'] }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="border-b border-gray-200 p-5 lg:border-b-0 lg:border-r dark:border-gray-700"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Templates
                            </p>

                            <p
                                class="mt-2 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ $evaluationOnboarding['usage']['templates']['used'] }}
                                <span
                                    class="text-sm font-medium text-gray-400"
                                >
                                    /
                                    {{ $evaluationOnboarding['usage']['templates']['limit'] }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="border-b border-gray-200 p-5 sm:border-r sm:border-b-0 dark:border-gray-700"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Active stations
                            </p>

                            <p
                                class="mt-2 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ $evaluationOnboarding['usage']['signing_stations']['used'] }}
                                <span
                                    class="text-sm font-medium text-gray-400"
                                >
                                    /
                                    {{ $evaluationOnboarding['usage']['signing_stations']['limit'] }}
                                </span>
                            </p>
                        </div>

                        <div class="p-5">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                Completed consents
                            </p>

                            <p
                                class="mt-2 text-xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ $evaluationOnboarding['usage']['completed_consents']['used'] }}
                                <span
                                    class="text-sm font-medium text-gray-400"
                                >
                                    /
                                    {{ $evaluationOnboarding['usage']['completed_consents']['limit'] }}
                                </span>
                            </p>
                        </div>
                    </div>
                </section>
            @endif

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
                            @if ($hasOrganizationAccess)
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
                            @else
                                <span
                                    class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300"
                                >
                                    Requires an active subscription
                                </span>
                            @endif
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
