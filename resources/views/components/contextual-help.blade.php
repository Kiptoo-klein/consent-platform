@php
    $helpUser = Auth::user();

    $helpContext = 'general';
    $helpTitle = 'Help & tips';

    $helpTips = [
        'Use the main navigation to move between templates, consent records, signing stations and organization settings.',
        'Return to the Dashboard for an overview of your organization activity.',
    ];

    if (request()->routeIs('dashboard')) {
        $helpContext = 'dashboard';
        $helpTitle = 'Help & tips';

        $helpTips = [
            'The Dashboard summarizes your organization activity and provides shortcuts into the main consent workflows.',
            'Recent records and templates help you return quickly to work that is already in progress.',
        ];

        if (
            $helpUser?->organization
                ?->subscription
                ?->isEvaluation()
        ) {
            array_unshift(
                $helpTips,
                'Use the Free Evaluation Getting Started checklist to work through the core eConsent workflow.'
            );
        }
    } elseif (
        request()->routeIs(
            'organization-settings.*',
            'organization-users.*',
            'organization-subscription.*',
            'organization-subscription-plans.*',
            'organization-billing.*',
            'organization-branding.*'
        )
    ) {
        $helpContext = 'settings';
        $helpTitle = 'Settings help';

        $helpTips = [
            'Settings brings organization access, branding, subscription and billing options into one place.',
            'Organization administrators can manage users, roles and branding.',
            'Billing options are available according to your organization role and billing ownership.',
        ];
    } elseif (
        request()->routeIs(
            'consent-templates.*'
        )
    ) {
        $helpContext = 'templates';
        $helpTitle = 'Consent template help';

        $helpTips = [
            'Draft templates can be edited without making them available for signing.',
            'Publish a template before using it for consent records or a signing station.',
            'Taking a template offline also makes workflows that depend on that live template unavailable until it is published again.',
        ];
    } elseif (
        request()->routeIs(
            'signing-stations.*'
        )
    ) {
        $helpContext = 'signing-stations';
        $helpTitle = 'Signing station help';

        $helpTips = [
            'A signing station needs a published consent template before it can be activated.',
            'QR expiry controls new QR scans; it does not by itself end an already configured shared kiosk workflow.',
            'If an assigned template is taken offline, the station must be activated again after the template is republished.',
        ];
    } elseif (
        request()->routeIs(
            'consent-sessions.*'
        )
    ) {
        $helpContext = 'consent-records';
        $helpTitle = 'Consent record help';

        $helpTips = [
            'Consent Records shows the signing status and evidence captured for each consent workflow.',
            'Completed records can include a generated signed PDF and the audit evidence associated with the signing process.',
            'Generated completed-consent PDFs are preserved as the record that was produced at completion time.',
        ];
    }

    $helpLinks = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
        ],
        [
            'label' => 'Consent Templates',
            'route' => 'consent-templates.index',
        ],
        [
            'label' => 'Consent Records',
            'route' => 'consent-sessions.index',
        ],
        [
            'label' => 'Signing Stations',
            'route' => 'signing-stations.index',
        ],
        [
            'label' => 'Settings',
            'route' => 'organization-settings.index',
        ],
    ];
@endphp

@if ($helpUser?->organization_id !== null)
    <div
        x-data="{ helpOpen: false }"
        data-contextual-help
        data-help-context="{{ $helpContext }}"
        @keydown.escape.window="helpOpen = false"
    >
        <button
            type="button"
            data-help-trigger
            @click="helpOpen = true"
            x-show="! helpOpen"
            :aria-expanded="helpOpen ? 'true' : 'false'"
            aria-controls="econsent-contextual-help-panel"
            class="fixed bottom-5 right-5 z-40 inline-flex items-center gap-2 rounded-full bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-950"
        >
            <span
                class="flex h-6 w-6 items-center justify-center rounded-full border border-white/50 text-sm font-bold"
                aria-hidden="true"
            >
                ?
            </span>

            <span>Help</span>
        </button>

        <div
            x-show="helpOpen"
            x-transition.opacity
            @click="helpOpen = false"
            class="fixed inset-0 z-50 bg-gray-950/40 backdrop-blur-sm"
            style="display: none;"
            aria-hidden="true"
        ></div>

        <aside
            id="econsent-contextual-help-panel"
            x-show="helpOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            role="dialog"
            aria-modal="true"
            aria-labelledby="econsent-contextual-help-title"
            class="fixed inset-y-0 right-0 z-[60] flex w-full max-w-md flex-col border-l border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
            style="display: none;"
        >
            <div
                class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5 dark:border-gray-700"
            >
                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400"
                    >
                        eConsent
                    </p>

                    <h2
                        id="econsent-contextual-help-title"
                        class="mt-1 text-xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ $helpTitle }}
                    </h2>
                </div>

                <button
                    type="button"
                    @click="helpOpen = false"
                    class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                >
                    <span class="sr-only">Close help</span>

                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <div
                class="flex-1 overflow-y-auto px-6 py-6"
            >
                <section>
                    <h3
                        class="text-sm font-bold text-gray-900 dark:text-white"
                    >
                        Tips for this page
                    </h3>

                    <ul
                        class="mt-4 space-y-4"
                    >
                        @foreach ($helpTips as $tip)
                            <li
                                class="flex gap-3 text-sm leading-6 text-gray-600 dark:text-gray-300"
                            >
                                <span
                                    class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"
                                    aria-hidden="true"
                                ></span>

                                <span>{{ $tip }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section
                    class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700"
                >
                    <h3
                        class="text-sm font-bold text-gray-900 dark:text-white"
                    >
                        Useful places
                    </h3>

                    <div
                        class="mt-4 grid gap-2"
                    >
                        @foreach ($helpLinks as $helpLink)
                            <a
                                href="{{ route($helpLink['route']) }}"
                                class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-gray-700 dark:text-gray-300 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-300"
                            >
                                <span>
                                    {{ $helpLink['label'] }}
                                </span>

                                <span aria-hidden="true">
                                    →
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        </aside>
    </div>
@endif
