{{-- FORGOT_PASSWORD_REDESIGN_V1 --}}
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
        Reset Password | {{ config('app.name', 'Consent Platform') }}
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

                    Secure account recovery
                </div>

                <h1 class="max-w-lg text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
                    Recover access to your
                    <span class="text-[#5EEAD4]">
                        consent workspace.
                    </span>
                </h1>

                <p class="mt-6 max-w-lg text-base leading-7 text-white/65 xl:text-lg">
                    Request a secure reset link and return to managing
                    templates, signing stations and consent records.
                </p>

                <div class="mt-10 max-w-lg rounded-2xl border border-white/10 bg-white/5 p-6">
                    <div class="flex items-start gap-4">
                        <span
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0F766E]/25 text-[#5EEAD4]"
                        >
                            <svg
                                class="h-6 w-6"
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
                        </span>

                        <div>
                            <p class="font-semibold">
                                Private and secure
                            </p>

                            <p class="mt-2 text-sm leading-6 text-white/55">
                                A reset link will only be sent to the email
                                address associated with your account.
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

        {{-- Reset panel --}}
        <section
            class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8 lg:px-12"
        >
            <div class="w-full max-w-md">

                {{-- Mobile branding --}}
                <a
                    href="{{ url('/') }}"
                    class="mb-10 inline-flex items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#0F766E] focus:ring-offset-4 lg:hidden"
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
                    <div
                        class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0F766E]/10 text-[#0F766E]"
                    >
                        <svg
                            class="h-6 w-6"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.75 5.25a3.75 3.75 0 1 1-7.5 0m7.5 0a3.75 3.75 0 0 0-7.5 0m7.5 0v1.5a3.75 3.75 0 0 1-7.5 0v-1.5M6 12.75h12A2.25 2.25 0 0 1 20.25 15v3.75A2.25 2.25 0 0 1 18 21H6a2.25 2.25 0 0 1-2.25-2.25V15A2.25 2.25 0 0 1 6 12.75Z"
                            />
                        </svg>
                    </div>

                    <p class="text-sm font-semibold text-[#0F766E]">
                        Account recovery
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-[#18181B]">
                        Reset your password
                    </h1>

                    <p class="mt-3 text-sm leading-6 text-gray-500">
                        Enter the email address associated with your account.
                        We will send you a secure password-reset link.
                    </p>
                </div>

                @if (session('status'))
                    <div
                        role="status"
                        class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
                    >
                        <div class="flex items-start gap-3">
                            <svg
                                class="mt-0.5 h-5 w-5 shrink-0"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m4.5 12.75 6 6 9-13.5"
                                />
                            </svg>

                            <span>
                                {{ session('status') }}
                            </span>
                        </div>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('password.email') }}"
                    class="space-y-6"
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
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="email"
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

                    <button
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#0F766E] px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-[#115E59] focus:outline-none focus:ring-4 focus:ring-[#0F766E]/20"
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
                                d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"
                            />
                        </svg>

                        Send reset link
                    </button>
                </form>

                <div class="mt-8 border-t border-gray-200 pt-6 text-center">
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center gap-2 rounded-lg text-sm font-semibold text-[#0F766E] hover:text-[#115E59] focus:outline-none focus:ring-2 focus:ring-[#0F766E] focus:ring-offset-2"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m15.75 19.5-7.5-7.5 7.5-7.5"
                            />
                        </svg>

                        Back to sign in
                    </a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
