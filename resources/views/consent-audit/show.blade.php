<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Consent Audit Trail
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Permanent history for consent session
                    #{{ $consentSession->id }}
                </p>
            </div>

            <a
                href="{{ route('consent-sessions.show', $consentSession) }}"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Consent summary --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Consent record
                    </h3>
                </div>

                <div class="grid gap-6 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Signer
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $consentSession->signer_name ?: 'Not provided' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Consent template
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{
                                $consentSession->consentTemplateVersion?->title
                                ?? $consentSession->consentTemplate?->title
                                ?? 'Consent form'
                            }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Status
                        </p>

                        <p class="mt-1">
                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                {{ ucfirst(str_replace('_', ' ', $consentSession->status)) }}
                            </span>
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Completed
                        </p>

                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{
                                $consentSession->completed_at
                                    ? $consentSession->completed_at->format('j M Y, g:i A')
                                    : 'Not completed'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Audit events --}}
            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Event history
                        </h3>

                        <span class="text-sm text-gray-500">
                            {{ $auditEvents->count() }}
                            {{ Str::plural('event', $auditEvents->count()) }}
                        </span>
                    </div>
                </div>

                @if ($auditEvents->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-500">
                            No audit events have been recorded for this consent.
                        </p>
                    </div>
                @else
                    <div class="divide-y divide-gray-200">
                        @foreach ($auditEvents as $event)
                            @php
                                $eventLabel = match ($event->event_type) {
                                    'consent.completed' =>
                                        'Consent completed',

                                    'consent.pdf_generated' =>
                                        'PDF generated',

                                    'consent.pdf_emailed' =>
                                        'PDF emailed',

                                    'consent.email_skipped' =>
                                        'Email skipped',

                                    'consent.pdf_downloaded' =>
                                        'PDF downloaded',

                                    default =>
                                        Str::headline($event->event_type),
                                };

                                $actorName = $event->user?->name;

                                if (! $actorName) {
                                    $actorName = match ($event->event_type) {
                                        'consent.completed' =>
                                            'Signer',

                                        'consent.pdf_generated',
                                        'consent.pdf_emailed',
                                        'consent.email_skipped' =>
                                            'System',

                                        default =>
                                            'System',
                                    };
                                }
                            @endphp

                            <div class="px-6 py-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-sm font-semibold text-gray-900">
                                                {{ $eventLabel }}
                                            </h4>

                                            <span class="rounded bg-gray-100 px-2 py-1 font-mono text-xs text-gray-600">
                                                {{ $event->event_type }}
                                            </span>
                                        </div>

                                        <p class="mt-2 text-sm leading-6 text-gray-700">
                                            {{ $event->description }}
                                        </p>

                                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                                            <div>
                                                <dt class="font-medium text-gray-500">
                                                    Actor
                                                </dt>

                                                <dd class="mt-1 text-gray-900">
                                                    {{ $actorName }}
                                                </dd>
                                            </div>

                                            @if ($event->ip_address)
                                                <div>
                                                    <dt class="font-medium text-gray-500">
                                                        IP address
                                                    </dt>

                                                    <dd class="mt-1 font-mono text-gray-900">
                                                        {{ $event->ip_address }}
                                                    </dd>
                                                </div>
                                            @endif

                                            <div>
                                                <dt class="font-medium text-gray-500">
                                                    Recorded
                                                </dt>

                                                <dd class="mt-1 text-gray-900">
                                                    {{ $event->created_at->format('j M Y, g:i:s A') }}
                                                </dd>
                                            </div>
                                        </dl>

                                        @if (! empty($event->metadata))
                                            <details class="mt-4">
                                                <summary class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                                    View event details
                                                </summary>

                                                <div class="mt-3 overflow-x-auto rounded-md bg-gray-900 p-4">
                                                    <pre class="text-xs leading-5 text-gray-100">{{ json_encode(
                                                        $event->metadata,
                                                        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                                                    ) }}</pre>
                                                </div>
                                            </details>
                                        @endif

                                        @if ($event->user_agent)
                                            <details class="mt-3">
                                                <summary class="cursor-pointer text-sm font-medium text-gray-600 hover:text-gray-900">
                                                    View device information
                                                </summary>

                                                <p class="mt-2 break-words rounded-md bg-gray-50 p-3 text-xs text-gray-600">
                                                    {{ $event->user_agent }}
                                                </p>
                                            </details>
                                        @endif
                                    </div>

                                    <time
                                        datetime="{{ $event->created_at->toIso8601String() }}"
                                        class="shrink-0 text-xs text-gray-500"
                                    >
                                        {{ $event->created_at->diffForHumans() }}
                                    </time>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
