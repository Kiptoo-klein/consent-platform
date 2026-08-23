@php
    $helpUser = Auth::user();

    $help = app(
        \App\Services\ContextualHelpService::class
    )->resolve(
        request()->route()?->getName(),
        $helpUser
    );

    $helpContext = $help['context'];
    $helpTitle = $help['title'];
    $helpIntro = $help['intro'];
    $helpSections = $help['sections'];
    $helpLinks = $help['links'];

    $supportRequestAvailable =
        Route::has(
            'support-request.store'
        );

    $supportRequestErrors =
        isset($errors)
            ? $errors->getBag(
                'supportRequest'
            )
            : new \Illuminate\Support\MessageBag();

    $openHelpForSupport =
        $supportRequestErrors->any()
        || session()->has(
            'support_request_status'
        );
@endphp

@if ($helpUser?->organization_id !== null)
    <div
        x-data="{ helpOpen: @js($openHelpForSupport) }"
        data-contextual-help
        data-help-context="{{ $helpContext }}"
        @keydown.escape.window="helpOpen = false"
    >
        <button
            type="button"
            data-help-trigger
            @click="helpOpen = true"
            x-show="! helpOpen"
            :aria-expanded="helpOpen ? 'true' : 'false'"
            aria-controls="econsent-contextual-help-panel"
            class="fixed bottom-5 right-5 z-40 inline-flex items-center gap-2 rounded-full bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-950"
        >
            <span
                class="flex h-6 w-6 items-center justify-center rounded-full border border-white/50 text-sm font-bold"
                aria-hidden="true"
            >
                ?
            </span>

            <span>
                Help
            </span>
        </button>

        <div
            x-show="helpOpen"
            x-transition.opacity
            @click="helpOpen = false"
            class="fixed inset-0 z-50 bg-gray-950/40 backdrop-blur-sm"
            style="display: none;"
            aria-hidden="true"
        ></div>

        <aside
            id="econsent-contextual-help-panel"
            x-show="helpOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            role="dialog"
            aria-modal="true"
            aria-labelledby="econsent-contextual-help-title"
            class="fixed inset-y-0 right-0 z-[60] flex w-full max-w-lg flex-col border-l border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
            style="display: none;"
        >
            {{-- Header --}}
            <div
                class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5 dark:border-gray-700"
            >
                <div class="min-w-0">
                    <p
                        class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-400"
                    >
                        eConsent page guide
                    </p>

                    <h2
                        id="econsent-contextual-help-title"
                        class="mt-1 text-xl font-bold text-gray-900 dark:text-white"
                    >
                        {{ $helpTitle }}
                    </h2>
                </div>

                <button
                    type="button"
                    @click="helpOpen = false"
                    class="shrink-0 rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                >
                    <span class="sr-only">
                        Close help
                    </span>

                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            {{-- Scrollable content --}}
            <div
                class="flex-1 overflow-y-auto px-6 py-6"
            >
                {{-- Page introduction --}}
                <div
                    class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 dark:border-indigo-900 dark:bg-indigo-950/30"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300"
                            aria-hidden="true"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                />

                                <path
                                    stroke-linecap="round"
                                    d="M12 10v6M12 7h.01"
                                />
                            </svg>
                        </span>

                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-wide text-gray-900 dark:text-white"
                            >
                                About this page
                            </p>

                            <p
                                class="mt-1 text-sm leading-6 text-indigo-950 dark:text-indigo-100"
                            >
                                {{ $helpIntro }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Detailed sections --}}
                <div class="mt-7 space-y-5">
                    @foreach ($helpSections as $helpSection)
                        @php
                            $sectionType =
                                $helpSection['type']
                                ?? 'guide';

                            $isWarning =
                                $sectionType === 'warning';

                            $isImportant =
                                $sectionType === 'important';
                        @endphp

                        <section
                            data-help-section
                            data-help-section-type="{{ $sectionType }}"
                            @class([
                                'rounded-2xl p-5',

                                'border-2 border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/30' =>
                                    $isWarning,

                                'border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30' =>
                                    $isImportant,

                                'border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' =>
                                    !$isWarning
                                    && !$isImportant,
                            ])
                        >
                            <div class="flex items-start gap-3">
                                @if ($isWarning)
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300"
                                        aria-hidden="true"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M12 9v4m0 4h.01M10.3 3.8 2.4 17.5A2 2 0 0 0 4.1 20h15.8a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z"
                                            />
                                        </svg>
                                    </span>
                                @elseif ($isImportant)
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300"
                                        aria-hidden="true"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle
                                                cx="12"
                                                cy="12"
                                                r="9"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                d="M12 8v5m0 3h.01"
                                            />
                                        </svg>
                                    </span>
                                @else
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                        aria-hidden="true"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m5 12 4 4L19 6"
                                            />
                                        </svg>
                                    </span>
                                @endif

                                <div class="min-w-0 flex-1">
                                    @if ($isWarning)
                                        <p
                                            class="text-xs font-extrabold uppercase tracking-[0.14em] text-red-700 dark:text-red-300"
                                        >
                                            Warning
                                        </p>
                                    @elseif ($isImportant)
                                        <p
                                            class="text-xs font-extrabold uppercase tracking-[0.14em] text-amber-700 dark:text-amber-300"
                                        >
                                            Important
                                        </p>
                                    @else
                                        <p
                                            class="text-xs font-extrabold uppercase tracking-[0.14em] text-gray-500 dark:text-gray-400"
                                        >
                                            Guidance
                                        </p>
                                    @endif

                                    <h3
                                        @class([
                                            'mt-1 text-sm font-bold',

                                            'text-red-950 dark:text-red-100' =>
                                                $isWarning,

                                            'text-amber-950 dark:text-amber-100' =>
                                                $isImportant,

                                            'text-gray-900 dark:text-white' =>
                                                !$isWarning
                                                && !$isImportant,
                                        ])
                                    >
                                        {{ $helpSection['title'] }}
                                    </h3>

                                    <ul
                                        class="mt-3 space-y-3"
                                    >
                                        @foreach (
                                            $helpSection['tips']
                                            as $tip
                                        )
                                            <li
                                                class="flex gap-3 text-sm leading-6"
                                            >
                                                <span
                                                    @class([
                                                        'mt-2 h-1.5 w-1.5 shrink-0 rounded-full',

                                                        'bg-red-500' =>
                                                            $isWarning,

                                                        'bg-amber-500' =>
                                                            $isImportant,

                                                        'bg-indigo-500' =>
                                                            !$isWarning
                                                            && !$isImportant,
                                                    ])
                                                    aria-hidden="true"
                                                ></span>

                                                <span
                                                    @class([
                                                        'text-red-900 dark:text-red-200' =>
                                                            $isWarning,

                                                        'text-amber-900 dark:text-amber-200' =>
                                                            $isImportant,

                                                        'text-gray-600 dark:text-gray-300' =>
                                                            !$isWarning
                                                            && !$isImportant,
                                                    ])
                                                >
                                                    {{ $tip }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </section>
                    @endforeach
                </div>

                {{-- Useful links --}}
                @if (! empty($helpLinks))
                    <section
                        class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700"
                    >
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-white"
                        >
                            Useful places
                        </h3>

                        <p
                            class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400"
                        >
                            Related areas that may help you complete this task.
                        </p>

                        <div class="mt-4 grid gap-2">
                            @foreach ($helpLinks as $helpLink)
                                @if (
                                    isset($helpLink['route'])
                                    && Route::has(
                                        $helpLink['route']
                                    )
                                )
                                    <a
                                        href="{{ route(
                                            $helpLink['route']
                                        ) }}"
                                        class="group flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-700 dark:text-gray-300 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-300"
                                    >
                                        <span>
                                            {{ $helpLink['label'] }}
                                        </span>

                                        <span
                                            class="transition-transform group-hover:translate-x-0.5"
                                            aria-hidden="true"
                                        >
                                            →
                                        </span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Ask a question / Report a problem --}}
                <section
                    class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700"
                    data-support-request
                >
                    <h3
                        class="text-sm font-bold text-gray-900 dark:text-white"
                    >
                        Still need help?
                    </h3>

                    <p
                        class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400"
                    >
                        Ask eConsent Support a question or report a
                        problem without leaving this page.
                    </p>

                    @if (session('support_request_status'))
                        <div
                            class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200"
                            role="status"
                        >
                            {{ session('support_request_status') }}
                        </div>
                    @endif

                    @if ($supportRequestAvailable)
                        <form
                            method="POST"
                            action="{{ route('support-request.store') }}"
                            class="mt-4 space-y-4"
                            x-data="{
                                supportType: @js(
                                    old(
                                        'support_type',
                                        'question'
                                    )
                                )
                            }"
                        >
                            @csrf

                            <input
                                type="hidden"
                                name="support_context_route"
                                value="{{ request()->route()?->getName() }}"
                            >

                            <input
                                type="hidden"
                                name="support_context_path"
                                value="/{{ request()->path() }}"
                            >

                            <input
                                type="hidden"
                                name="support_help_context"
                                value="{{ $helpContext }}"
                            >

                            <fieldset>
                                <legend
                                    class="text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-300"
                                >
                                    What do you need?
                                </legend>

                                <div
                                    class="mt-2 grid gap-2 sm:grid-cols-2"
                                >
                                    <label
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 text-sm transition"
                                        :class="
                                            supportType === 'question'
                                                ? 'border-indigo-400 ring-2 ring-indigo-400/60'
                                                : 'border-gray-700 hover:border-gray-500'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            name="support_type"
                                            value="question"
                                            class="sr-only"
                                            x-model="supportType"
                                            @checked(
                                                old(
                                                    'support_type',
                                                    'question'
                                                ) === 'question'
                                            )
                                        >

                                        <span
                                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                                            :style="
                                                supportType === 'question'
                                                    ? 'border-color:#818cf8;background-color:#818cf8;'
                                                    : 'border-color:#d1d5db;background-color:#ffffff;'
                                            "
                                            aria-hidden="true"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-white"
                                                x-show="supportType === 'question'"
                                            ></span>
                                        </span>

                                        <span>
                                            <span
                                                class="block font-semibold text-gray-900 dark:text-white"
                                            >
                                                Ask a question
                                            </span>

                                            <span
                                                class="mt-0.5 block text-xs leading-5 text-gray-500 dark:text-gray-400"
                                            >
                                                Get help understanding
                                                or using eConsent.
                                            </span>
                                        </span>
                                    </label>

                                    <label
                                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 text-sm transition"
                                        :class="
                                            supportType === 'problem'
                                                ? 'border-red-400 ring-2 ring-red-400/60'
                                                : 'border-gray-700 hover:border-gray-500'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            name="support_type"
                                            value="problem"
                                            class="sr-only"
                                            x-model="supportType"
                                            @checked(
                                                old(
                                                    'support_type'
                                                ) === 'problem'
                                            )
                                        >

                                        <span
                                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                                            :style="
                                                supportType === 'problem'
                                                    ? 'border-color:#f87171;background-color:#f87171;'
                                                    : 'border-color:#d1d5db;background-color:#ffffff;'
                                            "
                                            aria-hidden="true"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-white"
                                                x-show="supportType === 'problem'"
                                            ></span>
                                        </span>

                                        <span>
                                            <span
                                                class="block font-semibold text-gray-900 dark:text-white"
                                            >
                                                Report a problem
                                            </span>

                                            <span
                                                class="mt-0.5 block text-xs leading-5 text-gray-500 dark:text-gray-400"
                                            >
                                                Tell support what is not
                                                working as expected.
                                            </span>
                                        </span>
                                    </label>
                                </div>

                                @error(
                                    'support_type',
                                    'supportRequest'
                                )
                                    <p
                                        class="mt-2 text-xs font-medium text-red-700 dark:text-red-300"
                                    >
                                        {{ $message }}
                                    </p>
                                @enderror
                            </fieldset>

                            <div>
                                <label
                                    for="support_message"
                                    class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-300"
                                >
                                    Message
                                </label>

                                <textarea
                                    id="support_message"
                                    name="support_message"
                                    rows="5"
                                    minlength="10"
                                    maxlength="5000"
                                    required
                                    placeholder="Describe your question or what happened. Include what you expected to happen if you are reporting a problem."
                                    class="mt-2 block w-full rounded-xl border-gray-300 bg-white text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-300 dark:bg-white dark:text-gray-900 dark:placeholder:text-gray-400"
                                    style="
                                        background-color:#ffffff !important;
                                        color:#111827 !important;
                                        -webkit-text-fill-color:#111827 !important;
                                        caret-color:#111827 !important;
                                    "
                                >{{ old('support_message') }}</textarea>

                                @error(
                                    'support_message',
                                    'supportRequest'
                                )
                                    <p
                                        class="mt-2 text-xs font-medium text-red-700 dark:text-red-300"
                                    >
                                        {{ $message }}
                                    </p>
                                @enderror

                                @error(
                                    'support',
                                    'supportRequest'
                                )
                                    <p
                                        class="mt-2 text-xs font-medium text-red-700 dark:text-red-300"
                                    >
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                            >
                                Send to eConsent Support
                            </button>

                            <p
                                class="text-xs leading-5 text-gray-500 dark:text-gray-400"
                            >
                                Your account, organization and current
                                page context are included automatically
                                so support can investigate efficiently.
                            </p>
                        </form>
                    @else
                        <div
                            class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200"
                        >
                            Support messaging is temporarily
                            unavailable. Please try again later.
                        </div>
                    @endif
                </section>
            </div>

            {{-- Footer --}}
            <div
                class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-950"
            >
                <p
                    class="text-xs leading-5 text-gray-500 dark:text-gray-400"
                >
                    This guide describes how this eConsent page works.
                    Review confirmation dialogs and on-page warnings before
                    making high-impact changes.
                </p>
            </div>
        </aside>
    </div>
@endif
