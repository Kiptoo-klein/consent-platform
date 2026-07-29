<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Activity Log Details
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Review audit event #{{ $activityLog->id }}.
                </p>
            </div>

            <a
                href="{{ route('platform.activity-logs.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Back to Activity Logs
            </a>
        </div>
    </x-slot>

    @php
        $action = (string) (
            $activityLog->action
            ?? 'activity'
        );

        $badgeClasses = match ($action) {
            'user.created' =>
                'border-blue-200 bg-blue-100 text-blue-800',

            'user.updated' =>
                'border-amber-200 bg-amber-100 text-amber-800',

            'user.enabled' =>
                'border-green-200 bg-green-100 text-green-800',

            'user.disabled' =>
                'border-orange-200 bg-orange-100 text-orange-800',

            'user.archived' =>
                'border-red-200 bg-red-100 text-red-800',

            'user.restored' =>
                'border-purple-200 bg-purple-100 text-purple-800',

            default =>
                'border-gray-200 bg-gray-100 text-gray-800',
        };

        $actionLabel = str($action)
            ->replace(['.', '_'], ' ')
            ->title();

        $properties = $activityLog->properties ?? [];

        if ($properties instanceof \Illuminate\Support\Collection) {
            $properties = $properties->all();
        }

        if (is_object($properties)) {
            $properties = (array) $properties;
        }

        if (! is_array($properties)) {
            $properties = [];
        }

        $oldValues = $properties['old']
            ?? $properties['old_values']
            ?? [];

        $newValues = $properties['new']
            ?? $properties['new_values']
            ?? [];

        $oldValues = is_array($oldValues)
            ? $oldValues
            : [];

        $newValues = is_array($newValues)
            ? $newValues
            : [];

        $changedFields = collect(array_keys($oldValues))
            ->merge(array_keys($newValues))
            ->unique()
            ->values();

        $usageSnapshot = $properties['usage_snapshot'] ?? [];
        $exceededLimits = $properties['exceeded_limits'] ?? [];

        $usageSnapshot = is_array($usageSnapshot)
            ? $usageSnapshot
            : [];

        $exceededLimits = is_array($exceededLimits)
            ? $exceededLimits
            : [];

        $formatActivityValue = static function ($value) {
            if (is_bool($value)) {
                return $value ? 'Yes' : 'No';
            }

            if (is_array($value) || is_object($value)) {
                return json_encode(
                    $value,
                    JSON_UNESCAPED_SLASHES
                );
            }

            if (is_null($value) || $value === '') {
                return '—';
            }

            return (string) $value;
        };
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Event
                        </p>

                        <h2 class="mt-1 text-2xl font-bold text-gray-900">
                            Activity #{{ $activityLog->id }}
                        </h2>
                    </div>

                    <span class="inline-flex self-start rounded-full border px-3 py-1 text-sm font-semibold {{ $badgeClasses }}">
                        {{ $actionLabel }}
                    </span>
                </div>
            </section>

            <div class="grid gap-5 md:grid-cols-2">
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-gray-900">
                        Actor
                    </h2>

                    <p class="mt-4 font-semibold text-gray-900">
                        {{ $activityLog->user?->name ?? 'System' }}
                    </p>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ $activityLog->user?->email
                            ?? 'Automated action' }}
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-gray-900">
                        Subject
                    </h2>

                    <div class="mt-4">
                        @if ($activityLog->subject)
                            <p class="font-semibold text-gray-900">
                                {{ $activityLog->subject->name
                                    ?? $activityLog->subject->title
                                    ?? 'Record #'.$activityLog->subject_id }}
                            </p>

                            <p class="mt-1 text-sm text-gray-500">
                                {{ class_basename(
                                    $activityLog->subject_type
                                ) }}
                            </p>
                        @elseif ($activityLog->subject_id)
                            <p class="font-semibold text-gray-900">
                                Record #{{ $activityLog->subject_id }}
                            </p>

                            <p class="mt-1 text-sm text-gray-500">
                                {{ class_basename(
                                    $activityLog->subject_type
                                ) }}
                            </p>
                        @else
                            <p class="text-sm text-gray-500">
                                No subject recorded
                            </p>
                        @endif
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-gray-900">
                        Organization
                    </h2>

                    <p class="mt-4 text-sm text-gray-700">
                        {{ $activityLog->organization?->name
                            ?? 'Platform' }}
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-gray-900">
                        Date and Time
                    </h2>

                    <p class="mt-4 font-semibold text-gray-900">
                        {{ $activityLog->created_at
                            ? $activityLog->created_at->format(
                                'M d, Y'
                            )
                            : '—' }}
                    </p>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ $activityLog->created_at
                            ? $activityLog->created_at->format(
                                'H:i:s'
                            )
                            : '' }}
                    </p>

                    @if ($activityLog->created_at)
                        <p class="mt-1 text-xs text-gray-400">
                            {{ $activityLog->created_at
                                ->diffForHumans() }}
                        </p>
                    @endif
                </section>
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-gray-900">
                    Description
                </h2>

                <p class="mt-4 text-sm leading-6 text-gray-700">
                    {{ $activityLog->description
                        ?: 'No description recorded.' }}
                </p>
            </section>

            <div class="grid gap-5 md:grid-cols-2">
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-gray-900">
                        IP Address
                    </h2>

                    <p class="mt-4 text-sm text-gray-700">
                        {{ $activityLog->ip_address
                            ?? 'Not recorded' }}
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="font-semibold text-gray-900">
                        Browser and Device
                    </h2>

                    <p class="mt-4 break-words text-sm leading-6 text-gray-700">
                        {{ $activityLog->user_agent
                            ?? 'Not recorded' }}
                    </p>
                </section>
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-gray-900">
                    Changes
                </h2>

                @if ($changedFields->isNotEmpty())
                    <div class="mt-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Field
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Before
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        After
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">
                                @foreach ($changedFields as $field)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-semibold text-gray-900">
                                            {{ str($field)
                                                ->replace('_', ' ')
                                                ->title() }}
                                        </td>

                                        <td class="px-4 py-4 text-sm text-red-700">
                                            {{ $formatActivityValue(
                                                $oldValues[$field] ?? null
                                            ) }}
                                        </td>

                                        <td class="px-4 py-4 text-sm text-green-700">
                                            {{ $formatActivityValue(
                                                $newValues[$field] ?? null
                                            ) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif (! empty($properties))
                    <pre class="mt-6 overflow-x-auto rounded-xl bg-gray-900 p-4 text-xs leading-6 text-gray-100">{{ json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                @else
                    <p class="mt-4 text-sm text-gray-500">
                        No change details were recorded for this event.
                    </p>
                @endif
            </section>
              @if (! empty($usageSnapshot))
                  <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                      <h2 class="font-semibold text-gray-900">
                          Usage Snapshot
                      </h2>

                      <p class="mt-1 text-sm text-gray-500">
                          Organization usage recorded when the plan changed.
                      </p>

                      <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                          @foreach ($usageSnapshot as $key => $value)
                              <div class="rounded-xl bg-gray-50 p-4">
                                  <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                      {{ str($key)
                                          ->replace('_', ' ')
                                          ->title() }}
                                  </dt>

                                  <dd class="mt-2 text-xl font-bold text-gray-900">
                                      {{ number_format((int) $value) }}
                                  </dd>
                              </div>
                          @endforeach
                      </dl>
                  </section>
              @endif

              @if (! empty($exceededLimits))
                  <section class="rounded-2xl border border-red-200 bg-red-50 p-6 shadow-sm">
                      <h2 class="font-semibold text-red-900">
                          Exceeded Limits
                      </h2>

                      <p class="mt-1 text-sm text-red-800">
                          Capacity exceeded immediately after the plan change.
                      </p>

                      <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                          @foreach ($exceededLimits as $limit)
                              <div class="rounded-xl border border-red-200 bg-white p-4">
                                  <p class="text-sm font-semibold text-gray-900">
                                      {{ $limit['label'] ?? 'Plan limit' }}
                                  </p>

                                  <p class="mt-2 text-sm text-gray-600">
                                      {{ number_format(
                                          (int) ($limit['used'] ?? 0)
                                      ) }}
                                      of
                                      {{ number_format(
                                          (int) ($limit['limit'] ?? 0)
                                      ) }}
                                  </p>

                                  <p class="mt-2 text-sm font-bold text-red-700">
                                      {{ number_format(
                                          (int) ($limit['overage'] ?? 0)
                                      ) }}
                                      over limit
                                  </p>
                              </div>
                          @endforeach
                      </div>
                  </section>
              @endif

        </div>
    </div>
</x-app-layout>
