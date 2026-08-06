<x-layouts.signing-station
    :organization="$organization"
    :station="$station"
    title="QR Signing Expired"
>
    <div class="w-full max-w-2xl">
        <div class="overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-black/30">
            <div class="p-8 text-center sm:p-12">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-amber-100 text-amber-700">
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
                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                        />
                    </svg>
                </div>

                <p class="mt-6 text-sm font-bold uppercase tracking-[0.2em] text-amber-600">
                    Signing period ended
                </p>

                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">
                    QR signing window has expired
                </h1>

                <p class="mx-auto mt-4 max-w-lg text-base leading-7 text-slate-600">
                    This QR code is no longer accepting new consent requests.
                </p>

                @if ($expiresAt)
                    <div class="mx-auto mt-7 max-w-md rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">
                            Expired
                        </p>

                        <p class="mt-2 text-lg font-bold text-amber-950">
                            {{
                                $expiresAt->format(
                                    'M d, Y H:i T'
                                )
                            }}
                        </p>
                    </div>
                @endif

                <p class="mx-auto mt-7 max-w-lg text-sm leading-6 text-slate-500">
                    Please contact a staff member if a new signing period is required.
                </p>
            </div>
        </div>
    </div>
</x-layouts.signing-station>
