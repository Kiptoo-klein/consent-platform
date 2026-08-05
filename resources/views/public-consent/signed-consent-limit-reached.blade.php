<x-layouts.public-consent
    title="Signing temporarily unavailable"
    :organization="$consentSession->organization"
    :organization-name="$consentSession->organization?->name ?? config('app.name')"
>
    <section class="rounded-2xl border border-amber-300 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
            <svg
                class="h-8 w-8"
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

        <p class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-amber-700">
            Signing temporarily unavailable
        </p>

        <h1 class="mt-3 text-2xl font-bold text-gray-950">
            This organization cannot accept another signed consent right now
        </h1>

        <p class="mx-auto mt-4 max-w-xl leading-7 text-gray-600">
            Please contact the organization for assistance or try the signing
            link again later.
        </p>
    </section>
</x-layouts.public-consent>
