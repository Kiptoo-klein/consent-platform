{{-- VERIFY_EMAIL_PAGE_REDESIGN_V1 --}}
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
        Verify Email | {{ config('app.name', 'Consent Platform') }}
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

                    One final step
                </div>

                <h1 class="max-w-lg text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
                    Confirm your email.
                    <span class="text-[#5EEAD4]">
                        Secure your workspace.
                    </span>
                </h1>

                <p class="mt-6 max-w-lg text-base leading-7 text-white/65 xl:text-lg">
                    Email verification protects your organization and makes
                    sure important consent notifications reach the right
                    account.
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
                                    d="M21.75 6.75v10.5A2.25 2.25 0 0 1 19.5 19.5h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0-8.69 5.793a2.25 2.25 0 0 1-2.12 0L2.25 6.75"
                                />
                            </svg>
                        </span>

                        <div>
                            <p class="font-semibold">
                                Check your inbox
                            </p>

                            <p class="mt-1 text-sm leading-6 text-white/55">
                                Open the verification message and follow the
                                secure link to continue.
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
                                    d="M9 12.75 11.25 15 15 9.75m6-3.75c-4.97 0-9-2.239-9-5 0 2.761-4.03 5-9 5v6c0 5.25 3.438 9.75 9 11.25 5.562-1.5 9-6 9-11.25V6Z"
                                />
                            </svg>
                        </span>

                        <div>
                            <p class="font-semibold">
                                Verification protects access
                            </p>

                            <p class="mt-1 text-sm leading-6 text-white/55">
                                Your organization workspace stays protected
                                until your email address is confirmed.
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
                    Secure account verification
                </span>
            </div>
        </section>

        {{-- Verification panel --}}
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

                <div
                    class="mb-7 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#0F766E]/10 text-[#0F766E]"
                >
                    <svg
                        class="h-7 w-7"
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

                <div>
                    <p class="text-sm font-semibold text-[#0F766E]">
                        Check your inbox
                    </p>

                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-[#18181B]">
                        Verify your email
                    </h1>

                    <p class="mt-3 text-sm leading-6 text-gray-500">
                        We sent a verification link to
                    </p>

                    <p class="mt-1 break-all text-sm font-semibold text-gray-800">
                        {{ auth()->user()->email }}
                    </p>
                </div>

                @if (session('status') === 'verification-link-sent')
                    <div
                        role="status"
                        class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                    >
                        <div class="flex items-start gap-3">
                            <svg
                                class="mt-0.5 h-5 w-5 shrink-0"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75m6-3.75c-4.97 0-9-2.239-9-5 0 2.761-4.03 5-9 5v6c0 5.25 3.438 9.75 9 11.25 5.562-1.5 9-6 9-11.25V6Z"
                                />
                            </svg>

                            <span>
                                A new verification link has been sent.
                                Check your inbox and spam folder.
                            </span>
                        </div>
                    </div>
                @endif

                <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-semibold text-gray-800">
                        Didn't receive the email?
                    </p>

                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        You can request another verification message.
                    </p>

                    <form
                        method="POST"
                        action="{{ route('verification.send') }}"
                        class="mt-5"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-xl bg-[#0F766E] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#115E59] focus:outline-none focus:ring-4 focus:ring-[#0F766E]/20"
                        >
                            Resend verification email
                        </button>
                    </form>
                </div>

                <div class="mt-7 flex items-center justify-between border-t border-gray-200 pt-6">
                    <p class="text-xs leading-5 text-gray-400">
                        Wrong account?
                    </p>

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="rounded text-sm font-semibold text-[#0F766E] transition hover:text-[#115E59] focus:outline-none focus:ring-2 focus:ring-[#0F766E] focus:ring-offset-2"
                        >
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
