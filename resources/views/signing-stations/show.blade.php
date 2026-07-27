<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="truncate text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                        {{ $signingStation->name }}
                    </h2>

                    @if ($signingStation->active)
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300">
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300">
                            Paused
                        </span>
                    @endif
                </div>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $signingStation->consentTemplate?->title ?? 'Consent template unavailable' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($signingStation->active)
                    <a
                        href="{{ route(
                            'public-signing-stations.show',
                            $signingStation->station_token
                        ) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        Launch Kiosk
                    </a>
                @endif

                <a
                    href="{{ route('signing-stations.edit', $signingStation) }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Edit
                </a>

                <a
                    href="{{ route('signing-stations.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Total records
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $signingStation->consent_sessions_count ?? 0 }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Completed
                    </p>

                    <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ $signingStation->completed_sessions_count ?? 0 }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Pending
                    </p>

                    <p class="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ $signingStation->pending_sessions_count ?? 0 }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        In progress
                    </p>

                    <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $signingStation->in_progress_sessions_count ?? 0 }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Cancelled
                    </p>

                    <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">
                        {{ $signingStation->cancelled_sessions_count ?? 0 }}
                    </p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Station access
                            </h3>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Open this link on a tablet, computer or shared kiosk device.
                            </p>
                        </div>

                        <div class="space-y-5 p-6">
                            <div>
                                <label
                                    for="station-url"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Public station link
                                </label>

                                <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                    <input
                                        id="station-url"
                                        type="text"
                                        readonly
                                        value="{{ route(
                                            'public-signing-stations.show',
                                            $signingStation->station_token
                                        ) }}"
                                        class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-700 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                                    >

                                    <button
                                        type="button"
                                        onclick="copyStationLink()"
                                        class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        Copy Link
                                    </button>
                                </div>

                                <p
                                    id="copy-message"
                                    class="mt-2 hidden text-sm font-medium text-green-600 dark:text-green-400"
                                >
                                    Station link copied.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                @if ($signingStation->active)
                                    <a
                                        href="{{ route(
                                            'public-signing-stations.show',
                                            $signingStation->station_token
                                        ) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                                    >
                                        Launch Kiosk
                                    </a>
                                @endif

                                <a
                                    href="{{ route(
                                        'signing-stations.qr-code.download',
                                        $signingStation
                                    ) }}"
                                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                >
                                    Download QR Code
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'signing-stations.toggle',
                                        $signingStation
                                    ) }}"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-semibold shadow-sm transition
                                            {{ $signingStation->active
                                                ? 'border-yellow-300 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 dark:border-yellow-700 dark:bg-yellow-950/40 dark:text-yellow-300'
                                                : 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-700 dark:bg-green-950/40 dark:text-green-300' }}"
                                    >
                                        {{ $signingStation->active ? 'Pause Station' : 'Activate Station' }}
                                    </button>
                                </form>

                                <button
                                    type="button"
                                    onclick="openRegenerateModal()"
                                    class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-100 dark:border-red-700 dark:bg-red-950/40 dark:text-red-300"
                                >
                                    Regenerate Link
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Recent consent records
                                </h3>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Latest consent activity from this station.
                                </p>
                            </div>

                            <a
                                href="{{ route('consent-sessions.index') }}"
                                class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                            >
                                View all
                            </a>
                        </div>

                        @if ($recentSessions->isEmpty())
                            <div class="px-6 py-12 text-center">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    No consent records yet.
                                </p>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Records created from this station will appear here.
                                </p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                Signer
                                            </th>

                                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                Reference
                                            </th>

                                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                Status
                                            </th>

                                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                Created
                                            </th>

                                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                        @foreach ($recentSessions as $session)
                                            <tr>
                                                <td class="whitespace-nowrap px-6 py-4">
                                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                                        {{ $session->signer_name ?: 'Unnamed signer' }}
                                                    </p>

                                                    @if ($session->signer_email)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $session->signer_email }}
                                                        </p>
                                                    @endif
                                                </td>

                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                    {{ $session->signer_reference ?: '—' }}
                                                </td>

                                                <td class="whitespace-nowrap px-6 py-4">
                                                    @php
                                                        $statusClasses = match ($session->status) {
                                                            'completed' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                                            'in_progress' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                            default => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                        };
                                                    @endphp

                                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                                        {{ str($session->status)->replace('_', ' ')->title() }}
                                                    </span>
                                                </td>

                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                    {{ $session->created_at?->format('d M Y, H:i') }}
                                                </td>

                                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                                    <a
                                                        href="{{ route('consent-sessions.show', $session) }}"
                                                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                                                    >
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            QR code
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Scan this code to open the public signing station.
                        </p>

                        <div class="mt-5 flex justify-center rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700">
                            <img
                                src="{{ route(
                                    'signing-stations.qr-code',
                                    $signingStation
                                ) }}"
                                alt="QR code for {{ $signingStation->name }}"
                                class="h-56 w-56 max-w-full"
                            >
                        </div>

                        <a
                            href="{{ route(
                                'signing-stations.qr-code.download',
                                $signingStation
                            ) }}"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Download QR Code
                        </a>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Station settings
                            </h3>

                            <a
                                href="{{ route('signing-stations.edit', $signingStation) }}"
                                class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                            >
                                Edit
                            </a>
                        </div>

                        <dl class="mt-5 space-y-4">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Consent template
                                </dt>

                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $signingStation->consentTemplate?->title ?? 'Unavailable' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Email address
                                </dt>

                                <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $signingStation->require_email ? 'Required' : 'Optional' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Reference
                                </dt>

                                <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $signingStation->require_reference ? 'Required' : 'Optional' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Auto reset
                                </dt>

                                <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $signingStation->auto_reset_seconds ?? 10 }} seconds
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    Created
                                </dt>

                                <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $signingStation->created_at?->format('d M Y, H:i') }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        id="regenerate-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="regenerate-modal-title"
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900">
            <h3
                id="regenerate-modal-title"
                class="text-lg font-semibold text-gray-900 dark:text-white"
            >
                Regenerate station link?
            </h3>

            <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                The current public link and QR code will stop working immediately.
                Devices using the old link will no longer be able to access this station.
            </p>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    onclick="closeRegenerateModal()"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Cancel
                </button>

                <form
                    method="POST"
                    action="{{ route(
                        'signing-stations.regenerate-token',
                        $signingStation
                    ) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700"
                    >
                        Regenerate Link
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function copyStationLink() {
            const input = document.getElementById('station-url');
            const message = document.getElementById('copy-message');

            navigator.clipboard.writeText(input.value)
                .then(() => {
                    message.classList.remove('hidden');

                    window.setTimeout(() => {
                        message.classList.add('hidden');
                    }, 2500);
                })
                .catch(() => {
                    input.select();
                    document.execCommand('copy');

                    message.classList.remove('hidden');

                    window.setTimeout(() => {
                        message.classList.add('hidden');
                    }, 2500);
                });
        }

        function openRegenerateModal() {
            const modal = document.getElementById('regenerate-modal');

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeRegenerateModal() {
            const modal = document.getElementById('regenerate-modal');

            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('regenerate-modal')
            ?.addEventListener('click', function (event) {
                if (event.target === this) {
                    closeRegenerateModal();
                }
            });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeRegenerateModal();
            }
        });
    </script>
</x-app-layout>
