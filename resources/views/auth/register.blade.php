{{-- REGISTER_PAGE_REDESIGN_V1 --}}
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

    <title>
        Create Account | {{ config('app.name', 'Consent Platform') }}
    </title>

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

                    Simple account setup
                </div>

                <h1 class="max-w-lg text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
                    Start managing consent
                    <span class="text-[#5EEAD4]">
                        with confidence.
                    </span>
                </h1>

                <p class="mt-6 max-w-lg text-base leading-7 text-white/65 xl:text-lg">
                    Create your account to access consent templates,
                    signing stations and securely organized records.
                </p>

                <div class="mt-10 space-y-4">
                    <div class="flex max-w-lg items-start gap-4 rounded-2xl border border-white/10 bg-white/5 p-5">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0F766E]/25 text-[#5EEAD4]"
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
                                    d="M9 12.75 11.25 15 15 9.75m6-3.75c-4.97 0-9-2.239-9-5-0 2.761-4.03 5-9 5v6c0 5.25 3.438 9.75 9 11.25 5.562-1.5 9-6 9-11.25V6Z"
                                />
                            </svg>
                        </span>

                        <div>
                            <p class="font-semibold">
                                Secure workspace
                            </p>

                            <p class="mt-1 text-sm leading-6 text-white/55">
                                Access your consent tools through a protected
                                account.
                            </p>
                        </div>
                    </div>

                    <div class="flex max-w-lg items-start gap-4 rounded-2xl border border-white/10 bg-white/5 p-5">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#D4A72C]/15 text-[#D4A72C]"
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
                                    d="M9 12h6m-6 4h6m2.25 5H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25A2.25 2.25 0 0 1 6.75 3h5.69c.597 0 1.169.237 1.591.659l4.31 4.31c.422.422.659.994.659 1.591v9.19A2.25 2.25 0 0 1 16.75 21Z"
                                />
                            </svg>
                        </span>

                        <div>
                            <p class="font-semibold">
                                Organized records
                            </p>

                            <p class="mt-1 text-sm leading-6 text-white/55">
                                Keep consent activity clear, consistent and
                                easy to review.
                            </p>
                        </div>
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

        {{-- Registration panel --}}
        <section
            class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8 lg:px-12"
        >
            <div class="w-full max-w-md">

                {{-- Mobile branding --}}
                <a
                    href="{{ url('/') }}"
                    class="mb-8 inline-flex items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0F766E] focus:ring-offset-4 lg:hidden"
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

                <div class="mb-7">
                    <p class="text-sm font-semibold text-[#0F766E]">
                        Get started
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-[#18181B]">
                        Create your account
                    </h1>

                    <p class="mt-3 text-sm leading-6 text-gray-500">
                        Enter your details to create a secure platform account.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('register') }}"
                    class="space-y-5"
                >
                    @csrf

                    {{-- REGISTRATION_ORGANIZATION_NAME_FIELD --}}
                    <div>
                        <label
                            for="organization_name"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Organization name
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
                                        d="M3.75 21h16.5M5.25 21V6.75L12 3l6.75 3.75V21M8.25 9.75h.008v.008H8.25V9.75Zm0 3h.008v.008H8.25v-.008Zm0 3h.008v.008H8.25v-.008Zm3.75-6h.008v.008H12V9.75Zm0 3h.008v.008H12v-.008Zm0 3h.008v.008H12v-.008Zm3.75-6h.008v.008h-.008V9.75Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"
                                    />
                                </svg>
                            </div>

                            <input
                                id="organization_name"
                                type="text"
                                name="organization_name"
                                value="{{ old('organization_name') }}"
                                required
                                autocomplete="organization"
                                placeholder="Your organization name"
                                class="block w-full rounded-xl border border-gray-300 bg-white py-3 pl-12 pr-4 text-sm text-gray-900 shadow-sm outline-none placeholder:text-gray-400 focus:border-[#0F766E] focus:ring-4 focus:ring-[#0F766E]/10"
                            >
                        </div>

                        @error('organization_name')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="name"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Full name
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
                                        d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0"
                                    />
                                </svg>
                            </div>

                            <input
                                id="name"
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="Your full name"
                                class="block w-full rounded-xl border border-gray-300 bg-white py-3 pl-12 pr-4 text-sm text-gray-900 shadow-sm outline-none placeholder:text-gray-400 focus:border-[#0F766E] focus:ring-4 focus:ring-[#0F766E]/10"
                            >
                        </div>

                        @error('name')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

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
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="username"
                                placeholder="name@example.com"
                                class="block w-full rounded-xl border border-gray-300 bg-white py-3 pl-12 pr-4 text-sm text-gray-900 shadow-sm outline-none placeholder:text-gray-400 focus:border-[#0F766E] focus:ring-4 focus:ring-[#0F766E]/10"
                            >
                        </div>

                        @error('email')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="password"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Password
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
                                        d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 10.5h10.5A2.25 2.25 0 0 0 19.5 18.75v-6A2.25 2.25 0 0 0 17.25 10.5H6.75A2.25 2.25 0 0 0 4.5 12.75v6A2.25 2.25 0 0 0 6.75 21Z"
                                    />
                                </svg>
                            </div>

                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="new-password"
                                placeholder="Create a password"
                                class="block w-full rounded-xl border border-gray-300 bg-white py-3 pl-12 pr-4 text-sm text-gray-900 shadow-sm outline-none placeholder:text-gray-400 focus:border-[#0F766E] focus:ring-4 focus:ring-[#0F766E]/10"
                            >
                        </div>

                        @error('password')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="password_confirmation"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Confirm password
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
                                        d="M9 12.75 11.25 15 15 9.75m6-3.75c-4.97 0-9-2.239-9-5-0 2.761-4.03 5-9 5v6c0 5.25 3.438 9.75 9 11.25 5.562-1.5 9-6 9-11.25V6Z"
                                    />
                                </svg>
                            </div>

                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="Repeat your password"
                                class="block w-full rounded-xl border border-gray-300 bg-white py-3 pl-12 pr-4 text-sm text-gray-900 shadow-sm outline-none placeholder:text-gray-400 focus:border-[#0F766E] focus:ring-4 focus:ring-[#0F766E]/10"
                            >
                        </div>

                        @error('password_confirmation')
                            <p class="mt-2 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="flex w-full items-center justify-center rounded-xl bg-[#0F766E] px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-[#115E59] focus:outline-none focus:ring-4 focus:ring-[#0F766E]/20"
                    >
                        Create account
                    </button>
                </form>

                <div class="mt-7 border-t border-gray-200 pt-6 text-center">
                    <p class="text-sm text-gray-500">
                        Already have an account?

                        <a
                            href="{{ route('login') }}"
                            class="ml-1 rounded font-semibold text-[#0F766E] hover:text-[#115E59] focus:outline-none focus:ring-2 focus:ring-[#0F766E] focus:ring-offset-2"
                        >
                            Sign in
                        </a>
                    </p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
