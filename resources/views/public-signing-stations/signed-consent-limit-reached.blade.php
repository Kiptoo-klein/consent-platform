<x-layouts.signing-station
    :organization="$station->organization"
    :station="$station"
    title="Signing temporarily unavailable"
>
    <div class="w-full max-w-2xl">
        <section class="overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-black/30">
            <div class="p-8 text-center sm:p-12">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-amber-100 text-amber-700">
                    <svg
                        class="h-10 w-10"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="M12 9v3.75m9-1.5A9 9 0 1 1 3 11.25a9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12V16.5Z"
                        />
                    </svg>
                </div>

                <p class="mt-6 text-sm font-bold uppercase tracking-[0.2em] text-amber-600">
                    Signing temporarily unavailable
                </p>

                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">
                    This organization cannot accept another signed consent right now
                </h1>

                <p class="mx-auto mt-4 max-w-lg text-base leading-7 text-slate-600">
                    Please contact the organization for assistance or try again later.
                </p>
            </div>
        </section>
    </div>
</x-layouts.signing-station>
