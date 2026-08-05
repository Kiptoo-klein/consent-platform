@props([
    'capacity',
    'bulk' => false,
    'showBar' => false,
])

@if (
    ($capacity['limit'] ?? null) !== null
    || $showBar
)
    @php
        $unlimited = ($capacity['limit'] ?? null) === null;
        $reached = (bool) ($capacity['reached'] ?? false);
        $warning = (bool) ($capacity['warning'] ?? false);
        $remaining = $unlimited
            ? null
            : (int) ($capacity['remaining'] ?? 0);
        $used = (int) ($capacity['used'] ?? 0);
        $limit = $unlimited
            ? null
            : (int) ($capacity['limit'] ?? 0);
        $percentage = $unlimited
            ? 0
            : (
                $limit <= 0
                    ? 100
                    : min(
                        100,
                        (int) floor(
                            ($used / $limit) * 100
                        )
                    )
            );
    @endphp

    <section
        @class([
            'rounded-xl border p-5',
            'border-red-300 bg-red-50 text-red-950' => $reached,
            'border-amber-300 bg-amber-50 text-amber-950' => ! $reached && $warning,
            'border-blue-200 bg-blue-50 text-blue-950' => ! $reached && ! $warning,
        ])
        data-signed-consent-capacity
        data-signed-consent-remaining="{{ $remaining }}"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em]">
                    Signed consents this billing period
                </p>

                <h2 class="mt-2 text-lg font-bold">
                    @if ($unlimited)
                        {{ $used }} completed — Unlimited plan
                    @else
                        {{ $used }} of {{ $limit }} completed
                    @endif
                </h2>

                <p class="mt-1 text-sm leading-6">
                    @if ($unlimited)
                        Completed consent records are not capped on your
                        current subscription plan.
                    @elseif ($reached)
                        Your organization has no signed-consent capacity
                        remaining. New signers cannot complete consent until
                        the period renews or the plan is upgraded.
                    @elseif ($warning)
                        {{ $remaining }} signed
                        {{ \Illuminate\Support\Str::plural('consent', $remaining) }}
                        remaining. Consider upgrading before the limit is reached.
                    @else
                        {{ $remaining }} signed
                        {{ \Illuminate\Support\Str::plural('consent', $remaining) }}
                        remaining.
                    @endif
                </p>

                @if (($capacity['period_end'] ?? null) !== null)
                    <p class="mt-2 text-xs opacity-80">
                        Current period ends
                        {{ \App\Support\DisplayTime::format(
                            $capacity['period_end'],
                            'd M Y, H:i'
                        ) }}.
                    </p>
                @endif
            </div>

            @if (
                \Illuminate\Support\Facades\Route::has(
                    'organization-subscription-plans.index'
                )
            )
                <a
                    href="{{ route('organization-subscription-plans.index') }}"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800"
                >
                    View Upgrade Plans
                </a>
            @endif
        </div>


        @if ($showBar && ! $unlimited)
            <div class="mt-5">
                <div class="mb-2 flex items-center justify-between gap-4 text-xs font-semibold">
                    <span>
                        Usage
                    </span>

                    <span>
                        {{ $percentage }}%
                    </span>
                </div>

                <div
                    class="h-3 overflow-hidden rounded-full bg-white/80 ring-1 ring-black/10"
                    role="progressbar"
                    aria-label="Signed consent usage"
                    aria-valuemin="0"
                    aria-valuemax="{{ $limit }}"
                    aria-valuenow="{{ min($used, $limit) }}"
                >
                    <div
                        @class([
                            'h-full rounded-full transition-all',
                            'bg-red-600' => $reached,
                            'bg-amber-500' => ! $reached && $warning,
                            'bg-emerald-600' => ! $reached && ! $warning,
                        ])
                        style="width: {{ $percentage }}%"
                    ></div>
                </div>

                <div class="mt-2 flex flex-col gap-1 text-xs sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        {{ $remaining }}
                        signed
                        {{ \Illuminate\Support\Str::plural(
                            'consent',
                            $remaining
                        ) }}
                        remaining
                    </span>

                    <span>
                        Counts completed records from individual, bulk,
                        and kiosk signing.
                    </span>
                </div>
            </div>
        @elseif ($showBar && $unlimited)
            <div class="mt-5 rounded-lg border border-emerald-200 bg-white/70 px-4 py-3 text-sm font-semibold text-emerald-900">
                Unlimited signed consent records
            </div>
        @endif

        @if ($bulk && ! $reached)
            <div
                id="bulk-signed-capacity-warning"
                class="mt-4 hidden rounded-lg border border-amber-300 bg-white/70 px-4 py-3 text-sm font-medium text-amber-950"
            >
                This campaign currently has more recipients than the
                {{ $remaining }} signed-consent slots remaining. Invitations
                may still be created, but recipients beyond the remaining
                allowance cannot complete until capacity renews or the plan
                is upgraded.
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const capacity = document.querySelector(
                        '[data-signed-consent-capacity]'
                    );

                    const recipientList = document.querySelector(
                        '[data-bulk-recipient-list]'
                    );

                    const warning = document.getElementById(
                        'bulk-signed-capacity-warning'
                    );

                    if (! capacity || ! recipientList || ! warning) {
                        return;
                    }

                    const remaining = Number(
                        capacity.dataset.signedConsentRemaining
                    );

                    const refreshWarning = () => {
                        const recipientCount =
                            recipientList.querySelectorAll(
                                '[data-recipient-row]'
                            ).length;

                        warning.classList.toggle(
                            'hidden',
                            recipientCount <= remaining
                        );
                    };

                    new MutationObserver(refreshWarning).observe(
                        recipientList,
                        {
                            childList: true,
                        }
                    );

                    refreshWarning();
                });
            </script>
        @endif
    </section>
@endif
