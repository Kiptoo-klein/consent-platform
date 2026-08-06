<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <meta
            name="robots"
            content="noindex,nofollow"
        >

        <title>
            QR code unavailable | {{ config('app.name') }}
        </title>

        @vite([
            'resources/css/app.css',
            'resources/js/app.js',
        ])
    </head>

    <body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
        <main class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6">
            <section class="w-full max-w-xl overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-black/40">
                <div class="bg-gradient-to-br from-indigo-700 via-indigo-800 to-slate-900 px-6 py-10 text-center text-white sm:px-10">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl border border-white/20 bg-white/10">
                        <svg
                            class="h-10 w-10"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M6.75 6.75h.008v.008H6.75V6.75Zm0 10.5h.008v.008H6.75v-.008Zm10.5-10.5h.008v.008h-.008V6.75ZM3.75 3.75h6v6h-6v-6Zm0 10.5h6v6h-6v-6Zm10.5-10.5h6v6h-6v-6Zm0 10.5h2.25m3.75 0v2.25m-6 3.75h2.25m3.75-3.75v3.75h-3.75"
                            />
                        </svg>
                    </div>

                    <p class="mt-6 text-xs font-bold uppercase tracking-[0.22em] text-white/65">
                        QR code unavailable
                    </p>

                    <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                        This QR code is no longer available
                    </h1>
                </div>

                <div class="px-6 py-8 text-center sm:px-10 sm:py-10">
                    <p class="text-base leading-7 text-slate-600">
                        This kiosk may have been updated and a new
                        QR code created. The old printed poster can
                        no longer be used to begin a consent form.
                    </p>

                    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-left">
                        <p class="font-bold text-amber-900">
                            What should you do?
                        </p>

                        <p class="mt-2 text-sm leading-6 text-amber-800">
                            Ask a staff member for the latest QR
                            poster and scan the new code.
                        </p>
                    </div>

                    <p class="mt-6 text-sm leading-6 text-slate-500">
                        No consent information was submitted from
                        this unavailable QR code.
                    </p>
                </div>

                <footer class="border-t border-slate-200 bg-slate-50 px-6 py-4 text-center text-xs font-semibold text-slate-500">
                    Secure consent powered by
                    {{ config('app.name') }}
                </footer>
            </section>
        </main>
    </body>
</html>
