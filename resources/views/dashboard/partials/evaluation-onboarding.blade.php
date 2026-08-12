@php
    $usage = $evaluationOnboarding['usage'];
    $checklist = $evaluationOnboarding['checklist'];

    $steps = [
        [
            'key' => 'profile',
            'title' => 'Complete organization profile',
            'description' =>
                'Add your organization email, phone number and address.',
            'route' => route('organization-branding.edit'),
            'action' => 'Complete profile',
        ],
        [
            'key' => 'starter_reviewed',
            'title' => 'Review a starter template',
            'description' =>
                'Open one of the three starter templates and save your changes.',
            'route' => route('consent-templates.manage'),
            'action' => 'Review templates',
        ],
        [
            'key' => 'template_published',
            'title' => 'Publish your first template',
            'description' =>
                'Publish a working draft so it can be used for consent.',
            'route' => route('consent-templates.manage'),
            'action' => 'Open templates',
        ],
        [
            'key' => 'first_consent',
            'title' => 'Send or complete your first consent',
            'description' =>
                'Send an individual consent invitation or complete one through a signing station.',
            'route' => route(
                'consent-sessions.select-template',
                [
                    'self_test' => 1,
                ]
            ),
            'action' => 'Send test to myself',
        ],
        [
            'key' => 'signing_station',
            'title' => 'Create your first signing station',
            'description' =>
                'Create a public kiosk or QR signing station from a published template.',
            'route' => route('signing-stations.create'),
            'action' => 'Create station',
        ],
    ];
@endphp

<section
    data-evaluation-dashboard
    data-evaluation-email-usage="{{ $usage['emails']['used'] }}/{{ $usage['emails']['limit'] }}"
    data-evaluation-template-usage="{{ $usage['templates']['used'] }}/{{ $usage['templates']['limit'] }}"
    data-evaluation-signing-station-usage="{{ $usage['signing_stations']['used'] }}/{{ $usage['signing_stations']['limit'] }}"
    data-evaluation-consent-usage="{{ $usage['completed_consents']['used'] }}/{{ $usage['completed_consents']['limit'] }}"
    class="overflow-hidden rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 via-white to-violet-50 shadow-sm"
>
    <div class="border-b border-indigo-100 px-6 py-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl font-bold text-gray-900">
                        Free Evaluation
                    </h2>

                    <span
                        class="inline-flex rounded-full border border-indigo-200 bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-800"
                    >
                        No time limit
                    </span>
                </div>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">
                    Explore the individual consent workflow using your
                    starter templates, free invitation emails and public
                    signing station before choosing a paid plan.
                </p>
            </div>

            <a
                href="{{ route('organization-subscription.show') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
            >
                View plans
            </a>
        </div>
    </div>

    <div class="grid gap-px bg-indigo-100 sm:grid-cols-2 xl:grid-cols-4">
        <div class="bg-white/90 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Invitation emails
            </p>

            <p class="mt-2 text-2xl font-bold text-gray-900">
                {{ $usage['emails']['used'] }}
                <span class="text-base font-medium text-gray-400">
                    of {{ $usage['emails']['limit'] }}
                </span>
            </p>

            <p class="mt-1 text-xs text-gray-500">
                {{ $usage['emails']['remaining'] }} remaining
            </p>
        </div>

        <div class="bg-white/90 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Consent templates
            </p>

            <p class="mt-2 text-2xl font-bold text-gray-900">
                {{ $usage['templates']['used'] }}
                <span class="text-base font-medium text-gray-400">
                    of {{ $usage['templates']['limit'] }}
                </span>
            </p>

            <p class="mt-1 text-xs text-gray-500">
                Includes your 3 starter templates
            </p>
        </div>

        <div class="bg-white/90 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Active signing stations
            </p>

            <p class="mt-2 text-2xl font-bold text-gray-900">
                {{ $usage['signing_stations']['used'] }}
                <span class="text-base font-medium text-gray-400">
                    of {{ $usage['signing_stations']['limit'] }}
                </span>
            </p>

            <p class="mt-1 text-xs text-gray-500">
                Public kiosk or QR station
            </p>
        </div>

        <div class="bg-white/90 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Completed consents
            </p>

            <p class="mt-2 text-2xl font-bold text-gray-900">
                {{ $usage['completed_consents']['used'] }}
                <span class="text-base font-medium text-gray-400">
                    of {{ $usage['completed_consents']['limit'] }}
                </span>
            </p>

            <p class="mt-1 text-xs text-gray-500">
                Lifetime Evaluation usage
            </p>
        </div>
    </div>
</section>

<details
    data-evaluation-checklist
    data-evaluation-checklist-progress="{{ $evaluationOnboarding['completed_steps'] }}/{{ $evaluationOnboarding['total_steps'] }}"
    data-evaluation-checklist-complete="{{ $evaluationOnboarding['complete'] ? '1' : '0' }}"
    @if (! $evaluationOnboarding['complete']) open @endif
    class="overflow-hidden rounded-2xl bg-white shadow"
>
    <summary
        class="cursor-pointer list-none border-b border-gray-200 px-6 py-5"
    >
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900">
                    Getting started
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $evaluationOnboarding['completed_steps'] }}
                    of
                    {{ $evaluationOnboarding['total_steps'] }}
                    steps complete
                </p>
            </div>

            @if ($evaluationOnboarding['complete'])
                <span
                    class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800"
                >
                    Complete
                </span>
            @else
                <span
                    class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800"
                >
                    Continue setup
                </span>
            @endif
        </div>
    </summary>

    <div class="divide-y divide-gray-100">
        @foreach ($steps as $step)
            @php
                $completed = $checklist[$step['key']];
            @endphp

            <div
                data-evaluation-step="{{ $step['key'] }}"
                data-complete="{{ $completed ? '1' : '0' }}"
                class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex min-w-0 gap-4">
                    <div
                        @class([
                            'mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                            'bg-green-100 text-green-700' => $completed,
                            'bg-gray-100 text-gray-400' => ! $completed,
                        ])
                    >
                        @if ($completed)
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.704 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.25-3.25a1 1 0 011.414-1.414l2.543 2.543 6.543-6.543a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        @else
                            <span class="h-2.5 w-2.5 rounded-full bg-current"></span>
                        @endif
                    </div>

                    <div>
                        <p
                            @class([
                                'font-semibold',
                                'text-gray-500 line-through' => $completed,
                                'text-gray-900' => ! $completed,
                            ])
                        >
                            {{ $step['title'] }}
                        </p>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ $step['description'] }}
                        </p>
                    </div>
                </div>

                @if (! $completed)
                    <a
                        href="{{ $step['route'] }}"
                        class="shrink-0 text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                    >
                        {{ $step['action'] }}
                    </a>
                @endif
            </div>
        @endforeach
    </div>
</details>
