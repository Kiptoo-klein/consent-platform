{{-- LOGIN_PAGE_REDESIGN_V1 --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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

    <meta
        name="robots"
        content="noindex, nofollow"
    >

    <title>Sign In | {{ config('app.name', 'Consent Platform') }}</title>

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

<body class="min-h-screen bg-[#F7F8FA] font-sans text-[#18181B] antialiased">
    <main class="min-h-screen lg:grid lg:grid-cols-2">

        {{-- Brand panel --}}
        <section
            class="relative hidden min-h-screen overflow-hidden bg-[#18181B] px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between xl:px-20"
        >
            {{-- Decorative background --}}
            <div
                aria-hidden="true"
                class="absolute -right-32 -top-32 h-96 w-96 rounded-full border border-white/10"
            ></div>

            <div
                aria-hidden="true"
                class="absolute -bottom-40 -left-32 h-[28rem] w-[28rem] rounded-full bg-[#0F766E]/20"
            ></div>

            <div
                aria-hidden="true"
                class="absolute bottom-28 right-16 h-28 w-28 rounded-full border border-[#D4A72C]/25"
            ></div>

            <div class="relative z-10">
                <a
                    href="{{ url('/') }}"
                    class="inline-flex items-center gap-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#D4A72C] focus:ring-offset-4 focus:ring-offset-[#18181B]"
                >
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-white text-[#0F766E] shadow-lg"
                    >
                        <x-application-logo
                            class="h-7 w-7 fill-current"
                        />
                    </span>

                    <span>
                        <span class="block text-lg font-bold tracking-tight">
                            {{ config('app.name', 'Consent Platform') }}
                        </span>

                        <span class="block text-xs font-medium uppercase tracking-[0.2em] text-white/55">
                            Digital consent management
                        </span>
                    </span>
                </a>
            </div>

            <div class="relative z-10 max-w-xl">
                <div
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm text-white/75"
                >
                    <span class="h-2 w-2 rounded-full bg-[#D4A72C]"></span>
                    Secure digital consent
                </div>

                <h1 class="max-w-lg text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
                    Clear consent.
                    <span class="text-[#5EEAD4]">
                        Trusted records.
                    </span>
                </h1>

                <p class="mt-6 max-w-lg text-base leading-7 text-white/65 xl:text-lg">
                    Manage consent templates, signing stations and completed
                    records from one secure and organized platform.
                </p>

                <div class="mt-10 grid max-w-lg gap-5 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <svg
                            class="h-6 w-6 text-[#5EEAD4]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75m6-3.75c-4.97 0-9-2.239-9-5-0 2.761-4.03 5-9 5v6c0 5.25 3.438 9.75 9 11.25 5.562-1.5 9-6 9-11.25V6Z"
                            />
                        </svg>

                        <p class="mt-4 font-semibold">
                            Protected access
                        </p>

                        <p class="mt-1 text-sm leading-6 text-white/55">
                            Secure account access for authorized staff.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <svg
                            class="h-6 w-6 text-[#D4A72C]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2.25 5H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25A2.25 2.25 0 0 1 6.75 3h5.69c.597 0 1.169.237 1.591.659l4.31 4.31c.422.422.659.994.659 1.591v9.19A2.25 2.25 0 0 1 16.75 21Z"
                            />
                        </svg>

                        <p class="mt-4 font-semibold">
                            Reliable records
                        </p>

                        <p class="mt-1 text-sm leading-6 text-white/55">
                            Keep consent activity organized and accessible.
                        </p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 flex items-center justify-between text-xs text-white/40">
                <span>
                    © {{ date('Y') }}
                    {{ config('app.name', 'Consent Platform') }}
                </span>

                <span>
                    Authorized access only
                </span>
            </div>
        </section>

        {{-- Login panel --}}
        <section
            class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8 lg:px-12"
        >
            <div class="w-full max-w-md">

                {{-- Mobile branding --}}
                <a
                    href="{{ url('/') }}"
                    class="mb-10 inline-flex items-center gap-3 focus:outline-none focus:ring-2 focus:ring-[#0F766E] focus:ring-offset-4 lg:hidden"
                >
                    <span
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#18181B] text-white shadow-sm"
                    >
                        <x-application-logo
                            class="h-6 w-6 fill-current"
                        />
                    </span>

                    <span>
                        <span class="block font-bold">
                            {{ config('app.name', 'Consent Platform') }}
                        </span>

                        <span class="block text-xs text-gray-500">
                            Digital consent management
                        </span>
                    </span>
                </a>

                <div class="mb-8">
                    <p class="text-sm font-semibold text-[#0F766E]">
                        Welcome back
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-[#18181B]">
                        Sign in to your account
                    </h1>

                    <p class="mt-3 text-sm leading-6 text-gray-500">
                        Enter your account details to continue to the platform.
                    </p>
                </div>

                @if (session('status'))
                    <div
                        role="status"
                        class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                    >
                        {{ session('status') }}
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('login') }}"
                    class="space-y-5"
                >
                    @csrf

                    <div>
                        <label
                            for="email"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Email address
                        </label>

                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400"
                            >
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21.75 6.75v10.5A2.25 2.25 0 0 1 19.5 19.5h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0-8.69 5.793a2.25 2.25 0 0 1-2.12 0L2.25 6.75"
                                    />
                                </svg>
                            </div>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                required
                                autofocus
                                placeholder="name@example.com"
                                class="block w-full rounded-xl border border-gray-300 bg-white py-3 pl-12 pr-4 text-sm text-gray-900 shadow-sm outline-none placeholder:text-gray-400 focus:border-[#0F766E] focus:ring-4 focus:ring-[#0F766E]/10"
                            >
                        </div>

                        @error('email')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label
                                for="password"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Password
                            </label>

                            @if (Route::has('password.request'))
                                <a
                                    href="{{ route('password.request') }}"
                                    class="rounded text-sm font-semibold text-[#0F766E] hover:text-[#115E59] focus:outline-none focus:ring-2 focus:ring-[#0F766E] focus:ring-offset-2"
                                >
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400"
                            >
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 10.5h10.5A2.25 2.25 0 0 0 19.5 18.75v-6A2.25 2.25 0 0 0 17.25 10.5H6.75A2.25 2.25 0 0 0 4.5 12.75v6A2.25 2.25 0 0 0 6.75 21Z"
                                    />
                                </svg>
                            </div>

                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                placeholder="Enter your password"
                                class="block w-full rounded-xl border border-gray-300 bg-white py-3 pl-12 pr-12 text-sm text-gray-900 shadow-sm outline-none placeholder:text-gray-400 focus:border-[#0F766E] focus:ring-4 focus:ring-[#0F766E]/10"
                            >

                            <button
                                id="password-toggle"
                                type="button"
                                aria-label="Show password"
                                aria-pressed="false"
                                class="absolute inset-y-0 right-0 flex items-center rounded-r-xl px-4 text-gray-400 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#0F766E]"
                            >
                                <svg
                                    id="password-show-icon"
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                    />
                                </svg>

                                <svg
                                    id="password-hide-icon"
                                    class="hidden h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m3 3 18 18M10.58 10.58A2 2 0 0 0 13.42 13.42M9.88 4.69A10.9 10.9 0 0 1 12 4.5c6 0 9.75 7.5 9.75 7.5a18.3 18.3 0 0 1-2.03 2.76M6.61 6.61C3.75 8.55 2.25 12 2.25 12s3.75 7.5 9.75 7.5a10.7 10.7 0 0 0 4.1-.81"
                                    />
                                </svg>
                            </button>
                        </div>

                        @error('password')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="flex items-center">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-[#0F766E] focus:ring-[#0F766E]"
                            @checked(old('remember'))
                        >

                        <label
                            for="remember"
                            class="ml-2 text-sm text-gray-600"
                        >
                            Keep me signed in
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="flex w-full items-center justify-center rounded-xl bg-[#0F766E] px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-[#115E59] focus:outline-none focus:ring-4 focus:ring-[#0F766E]/20"
                    >
                        Sign in
                    </button>
                </form>

                @if (Route::has('register'))
                    <div class="mt-8 text-center">
                        <p class="text-sm text-gray-500">
                            Need an organization account?

                            <a
                                href="{{ route('register') }}"
                                class="ml-1 rounded font-semibold text-[#0F766E] hover:text-[#115E59] focus:outline-none focus:ring-2 focus:ring-[#0F766E] focus:ring-offset-2"
                            >
                                Create an account
                            </a>
                        </p>
                    </div>
                @endif

                <div class="mt-8 border-t border-gray-200 pt-6">
                    <p class="flex items-center justify-center gap-2 text-xs text-gray-400">
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 10.5h10.5A2.25 2.25 0 0 0 19.5 18.75v-6A2.25 2.25 0 0 0 17.25 10.5H6.75A2.25 2.25 0 0 0 4.5 12.75v6A2.25 2.25 0 0 0 6.75 21Z"
                            />
                        </svg>

                        Your account information is transmitted securely.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput =
                document.getElementById('password');

            const toggleButton =
                document.getElementById('password-toggle');

            const showIcon =
                document.getElementById('password-show-icon');

            const hideIcon =
                document.getElementById('password-hide-icon');

            if (
                !passwordInput ||
                !toggleButton ||
                !showIcon ||
                !hideIcon
            ) {
                return;
            }

            toggleButton.addEventListener('click', function () {
                const passwordIsVisible =
                    passwordInput.type === 'text';

                passwordInput.type =
                    passwordIsVisible ? 'password' : 'text';

                toggleButton.setAttribute(
                    'aria-pressed',
                    passwordIsVisible ? 'false' : 'true'
                );

                toggleButton.setAttribute(
                    'aria-label',
                    passwordIsVisible
                        ? 'Show password'
                        : 'Hide password'
                );

                showIcon.classList.toggle(
                    'hidden',
                    !passwordIsVisible
                );

                hideIcon.classList.toggle(
                    'hidden',
                    passwordIsVisible
                );
            });
        });
    </script>
</body>
</html>
