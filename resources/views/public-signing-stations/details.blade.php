<x-layouts.signing-station
    :organization="$station->organization"
    :station="$station"
    :title="'Your Details'"
>
    @php
        $existingResponses = old('responses', []);
    @endphp

    <div class="w-full max-w-5xl">
        <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-white shadow-2xl shadow-black/40">
            <header class="station-brand-panel relative overflow-hidden px-6 py-8 text-white sm:px-10 sm:py-10">
                <div class="relative">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">
                                Step 2 of 3
                            </p>

                            <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                                Enter your details
                            </h1>

                            <p class="mt-3 max-w-2xl text-base leading-7 text-white/80">
                                Your information will be securely attached to this consent record.
                            </p>
                        </div>

                        <div class="shrink-0 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-wide text-white/60">
                                Document reviewed
                            </p>

                            <p class="mt-1 max-w-xs font-bold text-white">
                                {{ $station->consentTemplate?->title ?? 'Consent form' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-3 gap-2">
                        <div class="h-1.5 rounded-full bg-white"></div>
                        <div class="h-1.5 rounded-full bg-white"></div>
                        <div class="h-1.5 rounded-full bg-white/20"></div>
                    </div>

                    <div class="mt-3 grid grid-cols-3 gap-3 text-center text-xs font-semibold text-white/60">
                        <div class="text-white">Review</div>
                        <div class="text-white">Details</div>
                        <div>Sign</div>
                    </div>
                </div>
            </header>

            <div class="bg-slate-50 px-4 py-5 sm:px-8 sm:py-8">
                <form
                    method="POST"
                    action="{{ route('public-signing-stations.start', $station->station_token) }}"
                    class="space-y-6"
                    autocomplete="off"
                    id="station-details-form"
                    data-kiosk-active-form
                >
                    @csrf

                    @if ($errors->any())
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-1.5A9 9 0 1 1 3 11.25a9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12V16.5Z" />
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="text-sm font-bold text-red-900">
                                        Please correct the following
                                    </h2>

                                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                            <p class="station-primary-text text-xs font-bold uppercase tracking-[0.18em]">
                                Personal information
                            </p>

                            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">
                                Tell us who you are
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Required fields are marked with an asterisk.
                            </p>
                        </div>

                        <div class="space-y-6 px-6 py-7 sm:px-8">
                            {{-- KIOSK_STANDARD_SIGNER_NAME --}}
                            <div>
                                <label for="signer_name" class="block text-sm font-bold text-slate-800">
                                    Full name <span class="text-red-500">*</span>
                                </label>

                                <div class="relative mt-2">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                        </svg>
                                    </div>

                                    <input
                                        id="signer_name"
                                        name="signer_name"
                                        type="text"
                                        required
                                        autofocus
                                        autocomplete="name"
                                        value="{{ old('signer_name') }}"
                                        placeholder="Enter your full name"
                                        class="station-primary-ring block w-full rounded-2xl border border-slate-300 bg-white py-3.5 pl-12 pr-4 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400"
                                    >
                                </div>

                                @error('signer_name')
                                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- KIOSK_STANDARD_SIGNER_EMAIL --}}
                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <label for="signer_email" class="block text-sm font-bold text-slate-800">
                                        Email address
                                        @if ($station->require_email)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>

                                    @unless ($station->require_email)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                                            Optional
                                        </span>
                                    @endunless
                                </div>

                                <div class="relative mt-2">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21.75 6.75v10.5A2.25 2.25 0 0 1 19.5 19.5h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0-8.69 5.52a2.25 2.25 0 0 1-2.42 0L2.25 6.75" />
                                        </svg>
                                    </div>

                                    <input
                                        id="signer_email"
                                        name="signer_email"
                                        type="email"
                                        autocomplete="email"
                                        value="{{ old('signer_email') }}"
                                        placeholder="name@example.com"
                                        @required($station->require_email)
                                        class="station-primary-ring block w-full rounded-2xl border border-slate-300 bg-white py-3.5 pl-12 pr-4 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400"
                                    >
                                </div>

                                @error('signer_email')
                                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Only template-selected fields appear, directly after name and email. --}}
                            @include('public-signing-stations.partials.additional-fields-inline')
                        </div>
                    </section>

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 11.25h10.5A2.25 2.25 0 0 0 19.5 19.5v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-bold text-emerald-900">Your information is protected</p>
                                <p class="mt-1 text-sm leading-6 text-emerald-800">
                                    Your details will only be used to create and identify this consent record.
                                    This kiosk returns to its welcome screen after two minutes of inactivity.
                                </p>
                            </div>
                        </div>
                    </div>

                </form>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <form
                        method="POST"
                        action="{{ route('public-signing-stations.cancel', $station->station_token) }}"
                        data-kiosk-cancel-form
                    >
                        @csrf

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3.5 font-bold text-slate-700 transition hover:bg-slate-50 sm:w-auto"
                        >
                            Cancel
                        </button>
                    </form>

                    <button
                        type="submit"
                        form="station-details-form"
                        class="station-submit-button group inline-flex items-center justify-center gap-3 rounded-2xl px-6 py-3.5 font-bold text-white shadow-lg shadow-slate-950/20 transition duration-200 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none active:translate-y-0"
                    >
                        <span>Continue to Signature</span>
                        <svg class="h-5 w-5 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('public-signing-stations.partials.inactivity-timeout', [
        'cancelAction' => route('public-signing-stations.cancel', $station->station_token),
    ])
</x-layouts.signing-station>
