@props([
    'name',
    'title',
    'message',
    'confirmText' => 'Continue',
    'variant' => 'warning',
    'confirmEvent' => null,
])

@php
    $styles = match ($variant) {
        'success' => [
            'icon' => 'bg-emerald-100 text-emerald-700',
            'button' =>
                'bg-emerald-600 text-white '
                .'hover:bg-emerald-700 '
                .'focus:ring-emerald-500',
        ],

        'danger' => [
            'icon' => 'bg-red-100 text-red-700',
            'button' =>
                'bg-red-600 text-white '
                .'hover:bg-red-700 '
                .'focus:ring-red-500',
        ],

        default => [
            'icon' => 'bg-amber-100 text-amber-700',
            'button' =>
                'bg-amber-500 text-slate-950 '
                .'hover:bg-amber-400 '
                .'focus:ring-amber-500',
        ],
    };
@endphp

<div
    x-data="{ show: false }"
    x-on:open-modal.window="
        $event.detail === @js($name)
            ? show = true
            : null
    "
    x-on:close-modal.window="
        $event.detail === @js($name)
            ? show = false
            : null
    "
    x-on:keydown.escape.window="
        if (show) {
            show = false
        }
    "
    x-init="
        $watch('show', value => {
            document.body.classList.toggle(
                'overflow-hidden',
                value
            );

            if (value) {
                $nextTick(() => {
                    $refs.cancel.focus()
                })
            }
        })
    "
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto p-4 sm:p-6"
    style="display: none;"
    data-action-confirmation
>
    <div
        class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"
        x-on:click="show = false"
        aria-hidden="true"
    ></div>

    <section
        class="relative z-10 w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-black/30"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $name }}-title"
        aria-describedby="{{ $name }}-message"
        x-on:click.stop
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
    >
        <div class="h-1.5 w-full {{ $variant === 'danger'
            ? 'bg-red-500'
            : ($variant === 'success'
                ? 'bg-emerald-500'
                : 'bg-amber-500') }}"
        ></div>

        <div class="px-6 pb-6 pt-6 sm:px-7 sm:pt-7">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $styles['icon'] }}"
                >
                    @if ($variant === 'success')
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m4.5 12.75 4.5 4.5 10.5-10.5"
                            />
                        </svg>
                    @else
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m0 3.75h.008v.008H12V16.5ZM10.29 3.86l-7.5 13A1 1 0 0 0 3.66 18h16.68a1 1 0 0 0 .87-1.5l-7.5-13a1 1 0 0 0-1.74 0Z"
                            />
                        </svg>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <p
                        class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400"
                    >
                        Confirmation required
                    </p>

                    <h2
                        id="{{ $name }}-title"
                        class="mt-1.5 text-xl font-bold tracking-tight text-slate-900"
                    >
                        {{ $title }}
                    </h2>

                    <p
                        id="{{ $name }}-message"
                        class="mt-4 text-sm leading-6 text-slate-600"
                    >
                        {{ $message }}
                    </p>
                </div>
            </div>
        </div>

        <div
            class="border-t border-slate-200 bg-slate-50 px-6 py-4 sm:px-7"
        >
            <div
                class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
            >
                <button
                    type="button"
                    x-ref="cancel"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                    x-on:click="show = false"
                >
                    Go back
                </button>

                @if ($confirmEvent)
                    <button
                        type="button"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl px-5 py-2.5 text-sm font-bold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $styles['button'] }}"
                        x-on:click="
                            $dispatch(@js($confirmEvent));
                            show = false
                        "
                        data-action-confirmation-event
                    >
                        {{ $confirmText }}
                    </button>
                @else
                    <button
                        type="submit"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl px-5 py-2.5 text-sm font-bold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $styles['button'] }}"
                        data-action-confirmation-submit
                    >
                        {{ $confirmText }}
                    </button>
                @endif
            </div>
        </div>
    </section>
</div>
