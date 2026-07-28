<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full"
>
    <head>
        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
        >

        <title>{{ config('ui-brand.name', config('app.name', 'eConsent')) }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <link
            rel="preconnect"
            href="https://fonts.bunny.net"
        >

        <link
            href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap"
            rel="stylesheet"
        >

        @vite([
            'resources/css/app.css',
            'resources/js/app.js',
        ])
    </head>

    <body class="econsent-app h-full font-sans antialiased" data-route="{{ Route::currentRouteName() ?? 'unknown' }}" data-path="{{ request()->path() }}">>
        <div
            x-data="{
                mobileSidebarOpen: false,

                sidebarCollapsed:
                    localStorage.getItem('sidebarCollapsed') === 'true',
            }"
            class="min-h-screen bg-gray-100 dark:bg-gray-950"
        >
            @include('layouts.navigation')

            <div
                class="min-h-screen transition-all duration-300 lg:pl-64"
                :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'"
            >
                <div class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 dark:border-gray-700 dark:bg-gray-900 lg:hidden">
                    <button
                        type="button"
                        @click="mobileSidebarOpen = true"
                        class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        <span class="sr-only">Open navigation</span>

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
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    <a
                        href="{{ route('dashboard') }}"
                        class="flex items-center gap-2"
                    >
                        <x-application-logo
                            class="h-8 w-8 fill-current text-indigo-600"
                        />

                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ config('app.name', 'Consent Platform') }}
                        </span>
                    </a>

                    <a
                        href="{{ route('profile.edit') }}"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                    >
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </a>
                </div>

                @isset($header)
                    <header class="border-b border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                            @php
                                $globalBackFallback =
                                    request()->routeIs('platform.*')
                                    && \Illuminate\Support\Facades\Route::has(
                                        'platform.dashboard'
                                    )
                                        ? route('platform.dashboard')
                                        : (
                                            \Illuminate\Support\Facades\Route::has(
                                                'dashboard'
                                            )
                                                ? route('dashboard')
                                                : url('/')
                                        );

                                $globalPreviousUrl = url()->previous();
                                $globalCurrentUrl = url()->current();
                                $globalLocalRoot = request()
                                    ->getSchemeAndHttpHost();

                                $globalPreviousIsInternal =
                                    $globalPreviousUrl === $globalLocalRoot
                                    || str_starts_with(
                                        $globalPreviousUrl,
                                        $globalLocalRoot . '/'
                                    );

                                $globalBackUrl =
                                    $globalPreviousIsInternal
                                    && $globalPreviousUrl !== $globalCurrentUrl
                                        ? $globalPreviousUrl
                                        : $globalBackFallback;
                            @endphp

                            @unless (
                                request()->routeIs(
                                    'dashboard',
                                    'platform.dashboard'
                                )
                            )
                                <div class="mb-4">
                                    <x-back-button
                                        :href="$globalBackUrl"
                                    />
                                </div>
                            @endunless

                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
</body>
</html>
