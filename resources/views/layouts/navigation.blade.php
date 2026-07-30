@php
    /*
    |--------------------------------------------------------------------------
    | Account-aware navigation
    |--------------------------------------------------------------------------
    |
    | Platform administrators and organization users share this layout, but
    | they must not receive the same navigation links.
    |
    | Platform users have a platform_role_id and use platform.* routes.
    | Organization users have an organization_id and use organization routes.
    |
    */

    $isPlatformUser = Auth::user()->platform_role_id !== null;

    if ($isPlatformUser) {
        $homeRoute = 'platform.dashboard';
        $accountRoute = 'platform.dashboard';

        $navigationItems = [
            [
                'label' => 'Platform Dashboard',
                'route' => 'platform.dashboard',
                'active' => 'platform.dashboard',
                'icon' => 'dashboard',
            ],
            [
                'label' => 'Organizations',
                'route' => 'platform.organizations.index',
                'active' => 'platform.organizations.*',
                'icon' => 'templates',
            ],
            [
                'label' => 'Invoice Reminder Settings',
                'route' =>
                    'platform.subscription-invoice-reminder-settings.index',
                'active' =>
                    'platform.subscription-invoice-reminder-settings.*',
                'icon' => 'records',
            ],
            // PLATFORM_ADMIN_TOOLS_RELOCATION_NAV
            [
                'label' => 'Security Status',
                'route' => 'platform.security.status',
                'active' => 'platform.security.*',
                'icon' => 'records',
            ],
            [
                'label' => 'Email Diagnostics',
                'route' => 'platform.email-diagnostics.index',
                'active' => 'platform.email-diagnostics.*',
                'icon' => 'records',
            ],
                    // PRODUCTION_READINESS_PLATFORM_NAV
            [
                'label' => 'Production Readiness',
                'route' => 'platform.production-readiness.index',
                'active' => 'platform.production-readiness.*',
                'icon' => 'records',
            ],
];
    } else {
        $homeRoute = 'dashboard';
        $accountRoute = 'profile.edit';

        $organizationUser = Auth::user();

        $organizationUser->loadMissing(
            'organization.subscription'
        );

        app(
            \Spatie\Permission\PermissionRegistrar::class
        )->setPermissionsTeamId(
            $organizationUser->organization_id
        );

        $isOrganizationAdministrator =
            $organizationUser->hasRole(
                \App\Enums\OrganizationRole::
                    ORGANIZATION_ADMINISTRATOR->label()
            );

        $organizationSubscription =
            $organizationUser->organization?->subscription;

        $isBillingOwner =
            $organizationSubscription !== null
            && (int) $organizationSubscription
                ->billing_owner_user_id
                === (int) $organizationUser->id;

        $navigationItems = [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'active' => 'dashboard',
                'icon' => 'dashboard',
            ],
            [
                'label' => 'Consent Templates',
                'route' => 'consent-templates.index',
                'active' => 'consent-templates.*',
                'icon' => 'templates',
            ],
            [
                'label' => 'Consent Records',
                'route' => 'consent-sessions.index',
                'active' => 'consent-sessions.*',
                'icon' => 'records',
            ],
            [
                'label' => 'Signing Stations',
                'route' => 'signing-stations.index',
                'active' => 'signing-stations.*',
                'icon' => 'station',
            ],
        ];

        if ($isOrganizationAdministrator) {
            $navigationItems[] = [
                'label' => 'Manage Users',
                'route' => 'organization-users.index',
                'parameters' => [
                    'organization' =>
                        $organizationUser->organization_id,
                ],
                'active' => 'organization-users.*',
                'icon' => 'records',
            ];
        }

        $navigationItems[] = [
            'label' => 'Subscription',
            'route' => 'organization-subscription.show',
            'active' => 'organization-subscription.*',
            'icon' => 'templates',
        ];

        if (
            $isBillingOwner
            || $isOrganizationAdministrator
        ) {
            $navigationItems[] = [
                'label' => 'Billing',
                'route' => 'organization-billing.index',
                'active' => 'organization-billing.*',
                'icon' => 'records',
            ];
        }

        $navigationItems[] = [
            'label' => 'Organization Branding',
            'route' => 'organization-branding.edit',
            'active' => 'organization-branding.*',
            'icon' => 'templates',
        ];
    }
@endphp

<!-- Desktop Sidebar -->
<aside
    class="econsent-desktop-sidebar fixed inset-y-0 left-0 z-40 hidden border-r border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 lg:flex lg:flex-col"
>
    <!-- Logo -->
    <div class="flex h-16 shrink-0 items-center border-b border-gray-200 px-4 dark:border-gray-700">
        <a
            href="{{ route($homeRoute) }}"
            class="flex min-w-0 items-center gap-3"
        >
            <x-application-logo
                class="block h-9 w-9 shrink-0 fill-current text-indigo-600"
            />

            <div
                data-sidebar-label
                class="min-w-0"
            >
                <p class="truncate text-sm font-bold text-gray-900 dark:text-white">
                    {{ config('app.name', 'Consent Platform') }}
                </p>

                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                    eConsent Management
                </p>
            </div>
        </a>
    </div>

    <!-- Main Navigation -->
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
        @foreach ($navigationItems as $item)
            <a
                href="{{ route(
                    $item['route'],
                    $item['parameters'] ?? []
                ) }}"
                title="{{ $item['label'] }}"
                @class([
                    'group flex h-11 items-center rounded-lg px-3 text-sm font-medium transition',
                    'justify-center' => false,
                    'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' =>
                        request()->routeIs($item['active']),
                    'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' =>
                        ! request()->routeIs($item['active']),
                ])
                data-sidebar-row
            >
                @if ($item['icon'] === 'dashboard')
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.75 3.75h6.5v6.5h-6.5v-6.5Zm10 0h6.5v6.5h-6.5v-6.5Zm-10 10h6.5v6.5h-6.5v-6.5Zm10 0h6.5v6.5h-6.5v-6.5Z"
                        />
                    </svg>
                @elseif ($item['icon'] === 'templates')
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6.75 3.75h7.5l3 3v13.5H6.75V3.75Z"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M14.25 3.75v3h3M9 11.25h6M9 14.25h6M9 17.25h4"
                        />
                    </svg>
                @elseif ($item['icon'] === 'records')
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8.25 4.5h7.5M8.25 3h7.5a1.5 1.5 0 0 1 1.5 1.5V6h1.5a1.5 1.5 0 0 1 1.5 1.5v12H3.75v-12A1.5 1.5 0 0 1 5.25 6h1.5V4.5A1.5 1.5 0 0 1 8.25 3Z"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8.25 11.25h7.5M8.25 14.25h7.5M8.25 17.25h4.5"
                        />
                    </svg>
                @elseif ($item['icon'] === 'station')
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <rect
                            x="4"
                            y="3.75"
                            width="16"
                            height="12"
                            rx="1.5"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 20.25h6M12 15.75v4.5M8.25 8.25h7.5M8.25 11.25h4.5"
                        />
                    </svg>
                @endif

                <span
                    data-sidebar-label
                    class="truncate"
                >
                    {{ $item['label'] }}
                </span>
            </a>
        @endforeach
    </nav>

    <!-- User Area -->
    <div class="border-t border-gray-200 p-3 dark:border-gray-700">
        <a
           href="{{ route($accountRoute) }}"
            title="Profile"
            class="flex h-11 items-center rounded-lg px-3 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
            data-sidebar-row
        >
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>

            <div
                data-sidebar-label
                class="min-w-0 flex-1"
            >
                <p class="truncate font-semibold text-gray-800 dark:text-gray-100">
                    {{ Auth::user()->name }}
                </p>

                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                    {{ Auth::user()->email }}
                </p>
            </div>
        </a>

        <form
            method="POST"
            action="{{ route('logout') }}"
            class="mt-1"
        >
            @csrf

            <button
                type="submit"
                title="Log Out"
                class="flex h-11 w-full items-center rounded-lg px-3 text-sm font-medium text-gray-600 transition hover:bg-red-50 hover:text-red-700 dark:text-gray-300 dark:hover:bg-red-950/30 dark:hover:text-red-300"
                data-sidebar-row
            >
                <svg
                    class="h-5 w-5 shrink-0"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M14.25 8.25 18 12m0 0-3.75 3.75M18 12H8.25M11.25 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25h4.5"
                    />
                </svg>

                <span
                    data-sidebar-label
                >
                    Log Out
                </span>
            </button>
        </form>

        <!-- Collapse Button -->
        <button
            type="button"
            @click="
                sidebarCollapsed = !sidebarCollapsed;

                document.documentElement.classList.toggle(
                    'sidebar-is-collapsed',
                    sidebarCollapsed
                );

                localStorage.setItem(
                    'sidebarCollapsed',
                    sidebarCollapsed ? 'true' : 'false'
                );
            "
            class="mt-2 flex h-10 w-full items-center rounded-lg px-3 text-sm font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            data-sidebar-row
        >
            <svg
                class="h-5 w-5 shrink-0 transition-transform duration-300"
                :class="sidebarCollapsed ? 'rotate-180' : ''"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m15 18-6-6 6-6"
                />
            </svg>

            <span
                data-sidebar-label
            >
                Collapse Sidebar
            </span>
        </button>
    </div>
</aside>

<!-- Mobile Overlay -->
<div
    x-show="mobileSidebarOpen"
    x-transition.opacity
    @click="mobileSidebarOpen = false"
    class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden"
    style="display: none;"
></div>

<!-- Mobile Sidebar -->
<aside
    x-show="mobileSidebarOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    @keydown.escape.window="mobileSidebarOpen = false"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-white shadow-xl dark:bg-gray-900 lg:hidden"
    style="display: none;"
>
    <div class="flex h-16 items-center justify-between border-b border-gray-200 px-4 dark:border-gray-700">
        <a
            href="{{ route($homeRoute) }}"
            class="flex min-w-0 items-center gap-3"
        >
            <x-application-logo
                class="block h-9 w-9 shrink-0 fill-current text-indigo-600"
            />

            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-gray-900 dark:text-white">
                    {{ config('app.name', 'Consent Platform') }}
                </p>

                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                    eConsent Management
                </p>
            </div>
        </a>

        <button
            type="button"
            @click="mobileSidebarOpen = false"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
        >
            <span class="sr-only">Close menu</span>

            <svg
                class="h-6 w-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6 18 18 6M6 6l12 12"
                />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
        @foreach ($navigationItems as $item)
            <a
                href="{{ route(
                    $item['route'],
                    $item['parameters'] ?? []
                ) }}"
                @click="mobileSidebarOpen = false"
                @class([
                    'flex h-12 items-center gap-3 rounded-lg px-3 text-sm font-medium transition',
                    'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300' =>
                        request()->routeIs($item['active']),
                    'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' =>
                        ! request()->routeIs($item['active']),
                ])
            >
                @if ($item['icon'] === 'dashboard')
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.75 3.75h6.5v6.5h-6.5v-6.5Zm10 0h6.5v6.5h-6.5v-6.5Zm-10 10h6.5v6.5h-6.5v-6.5Zm10 0h6.5v6.5h-6.5v-6.5Z"
                        />
                    </svg>
                @elseif ($item['icon'] === 'templates')
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6.75 3.75h7.5l3 3v13.5H6.75V3.75Z"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M14.25 3.75v3h3M9 11.25h6M9 14.25h6M9 17.25h4"
                        />
                    </svg>
                @elseif ($item['icon'] === 'records')
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8.25 4.5h7.5M8.25 3h7.5a1.5 1.5 0 0 1 1.5 1.5V6h1.5a1.5 1.5 0 0 1 1.5 1.5v12H3.75v-12A1.5 1.5 0 0 1 5.25 6h1.5V4.5A1.5 1.5 0 0 1 8.25 3Z"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8.25 11.25h7.5M8.25 14.25h7.5M8.25 17.25h4.5"
                        />
                    </svg>
                @elseif ($item['icon'] === 'station')
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <rect
                            x="4"
                            y="3.75"
                            width="16"
                            height="12"
                            rx="1.5"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 20.25h6M12 15.75v4.5M8.25 8.25h7.5M8.25 11.25h4.5"
                        />
                    </svg>
                @endif

                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="border-t border-gray-200 p-3 dark:border-gray-700">
        <a
            href="{{ route($accountRoute) }}"
            class="flex items-center gap-3 rounded-lg px-3 py-3 hover:bg-gray-100 dark:hover:bg-gray-800"
        >
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>

            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                    {{ Auth::user()->name }}
                </p>

                <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                    {{ Auth::user()->email }}
                </p>
            </div>
        </a>

        <form
            method="POST"
            action="{{ route('logout') }}"
            class="mt-1"
        >
            @csrf

            <button
                type="submit"
                class="flex h-11 w-full items-center gap-3 rounded-lg px-3 text-sm font-medium text-gray-600 hover:bg-red-50 hover:text-red-700 dark:text-gray-300 dark:hover:bg-red-950/30 dark:hover:text-red-300"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M14.25 8.25 18 12m0 0-3.75 3.75M18 12H8.25M11.25 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25h4.5"
                    />
                </svg>

                Log Out
            </button>
        </form>
    </div>
</aside>
