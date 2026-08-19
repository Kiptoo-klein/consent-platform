<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Export data
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Choose the information you want to include from the matching consent records.
                </p>
            </div>

            <a
                href="{{ route('consent-sessions.index', $scopeParameters) }}"
                class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Change filters
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <section class="rounded-xl bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Records to export
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Your current Consent Records filters are already applied.
                    </p>
                </div>

                <div class="px-6 py-5">
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $matchingCount }}
                    </p>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ $matchingCount === 1 ? 'matching record' : 'matching records' }}
                    </p>

                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-sm text-gray-700">
                            <span class="font-medium">
                                Consent:
                            </span>

                            <span class="font-semibold">
                                {{ $selectedTemplate?->title ?? 'All matching consents' }}
                            </span>
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Change the consent, status, dates or other filters from Consent Records.
                        </p>
                    </div>
                </div>
            </section>

            @if ($matchingCount === 0)
                <section class="rounded-xl bg-white px-6 py-14 text-center shadow">
                    <h2 class="text-lg font-semibold text-gray-900">
                        No records to export
                    </h2>

                    <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500">
                        Return to Consent Records and change the active filters.
                    </p>
                </section>
            @else
                @php
                    $defaultColumns =
                        collect($signerColumns)
                            ->concat($questionColumns)
                            ->filter(
                                fn ($column) =>
                                    $column['selected_by_default']
                            )
                            ->pluck('key')
                            ->values()
                            ->all();
                @endphp

                <form
                    method="POST"
                    action="{{ route('consent-sessions.export-data.download') }}"
                    class="overflow-hidden rounded-xl bg-white shadow"
                    x-data="{
                        selected: @js($defaultColumns),
                        format: 'xlsx',

                        toggleAll(keys) {
                            const allSelected =
                                keys.every(
                                    (key) =>
                                        this.selected.includes(key)
                                );

                            if (allSelected) {
                                this.selected =
                                    this.selected.filter(
                                        (key) =>
                                            ! keys.includes(key)
                                    );

                                return;
                            }

                            keys.forEach(
                                (key) => {
                                    if (
                                        ! this.selected.includes(key)
                                    ) {
                                        this.selected.push(key);
                                    }
                                }
                            );
                        }
                    }"
                >
                    @csrf

                    @foreach ($scopeParameters as $key => $value)
                        <input
                            type="hidden"
                            name="{{ $key }}"
                            value="{{ $value }}"
                        >
                    @endforeach

                    <div class="border-b border-gray-200 px-6 py-5">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Choose columns
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Only information that exists in these matching records is shown.
                        </p>

                        @error('columns')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="space-y-8 px-6 py-6">
                        @if ($signerColumns->isNotEmpty())
                            <div>
                                <h3 class="font-semibold text-gray-900">
                                    Signer details
                                </h3>

                                <p class="mt-1 text-sm text-gray-500">
                                    Name and contact details are selected automatically when available.
                                </p>

                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    @foreach ($signerColumns as $column)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 hover:bg-gray-50">
                                            <input
                                                type="checkbox"
                                                name="columns[]"
                                                value="{{ $column['key'] }}"
                                                x-model="selected"
                                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            >

                                            <span>
                                                <span class="block font-medium text-gray-800">
                                                    {{ $column['label'] }}
                                                </span>

                                                @if (
                                                    ! $selectedTemplate
                                                    && isset($column['template_title'])
                                                )
                                                    <span class="mt-1 block text-xs text-gray-500">
                                                        {{ $column['template_title'] }}
                                                    </span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($questionColumns->isNotEmpty())
                            <div class="border-t border-gray-200 pt-7">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">
                                            Consent answers
                                        </h3>

                                        <p class="mt-1 text-sm text-gray-500">
                                            Choose any consent answers you want to include.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        @click="toggleAll(@js($questionColumns->pluck('key')->values()))"
                                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                                    >
                                        Select or clear all
                                    </button>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @foreach ($questionColumns as $column)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 hover:bg-gray-50">
                                            <input
                                                type="checkbox"
                                                name="columns[]"
                                                value="{{ $column['key'] }}"
                                                x-model="selected"
                                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            >

                                            <span>
                                                <span class="block font-medium text-gray-800">
                                                    {{ $column['label'] }}
                                                </span>

                                                @if (! $selectedTemplate)
                                                    <span class="mt-1 block text-xs text-gray-500">
                                                        {{ $column['template_title'] }}
                                                    </span>
                                                @endif
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (
                            $signerColumns->isNotEmpty()
                            || $questionColumns->isNotEmpty()
                        )
                            <div class="border-t border-gray-200 pt-7">
                                <h3 class="font-semibold text-gray-900">
                                    Export as
                                </h3>

                                <p class="mt-1 text-sm text-gray-500">
                                    Choose the file format you want to download.
                                </p>

                                <div class="mt-4 grid gap-3 md:grid-cols-3">
                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 hover:bg-gray-50">
                                        <input
                                            type="radio"
                                            name="format"
                                            value="xlsx"
                                            x-model="format"
                                            class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >

                                        <span>
                                            <span class="flex items-center justify-between gap-3">
                                                <span class="font-semibold text-gray-900">
                                                    Excel
                                                </span>

                                                <span
                                                    x-show="format === 'xlsx'"
                                                    x-cloak
                                                    class="rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white"
                                                >
                                                    Selected
                                                </span>
                                            </span>

                                            <span class="mt-1 block text-sm text-gray-500">
                                                .xlsx spreadsheet
                                            </span>
                                        </span>
                                    </label>

                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 hover:bg-gray-50">
                                        <input
                                            type="radio"
                                            name="format"
                                            value="csv"
                                            x-model="format"
                                            class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >

                                        <span>
                                            <span class="flex items-center justify-between gap-3">
                                                <span class="font-semibold text-gray-900">
                                                    CSV
                                                </span>

                                                <span
                                                    x-show="format === 'csv'"
                                                    x-cloak
                                                    class="rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white"
                                                >
                                                    Selected
                                                </span>
                                            </span>

                                            <span class="mt-1 block text-sm text-gray-500">
                                                .csv spreadsheet
                                            </span>
                                        </span>
                                    </label>

                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 hover:bg-gray-50">
                                        <input
                                            type="radio"
                                            name="format"
                                            value="pdf"
                                            x-model="format"
                                            class="mt-0.5 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >

                                        <span>
                                            <span class="flex items-center justify-between gap-3">
                                                <span class="font-semibold text-gray-900">
                                                    PDF Register
                                                </span>

                                                <span
                                                    x-show="format === 'pdf'"
                                                    x-cloak
                                                    class="rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white"
                                                >
                                                    Selected
                                                </span>
                                            </span>

                                            <span class="mt-1 block text-sm text-gray-500">
                                                Printable table · up to 12 columns
                                            </span>
                                        </span>
                                    </label>
                                </div>

                                @error('format')
                                    <p class="mt-2 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        @endif

                        @if (
                            $signerColumns->isEmpty()
                            && $questionColumns->isEmpty()
                        )
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                These records do not contain any exportable signer details or consent answers.
                            </div>
                        @endif
                    </div>

                    @if (
                        $signerColumns->isNotEmpty()
                        || $questionColumns->isNotEmpty()
                    )
                        <div class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700">
                                    <span x-text="selected.length"></span>
                                    <span x-text="selected.length === 1 ? 'column selected' : 'columns selected'"></span>
                                </p>

                                <p
                                    x-show="format === 'pdf' && selected.length > 12"
                                    x-cloak
                                    class="mt-1 text-sm text-amber-700"
                                >
                                    PDF Register supports up to 12 columns. Choose fewer columns or use Excel or CSV.
                                </p>
                            </div>

                            <button
                                type="submit"
                                :disabled="
                                    selected.length === 0
                                    || (format === 'pdf' && selected.length > 12)
                                "
                                class="inline-flex justify-center rounded-lg px-5 py-3 text-sm font-semibold"
                                :class="
                                    selected.length === 0
                                    || (format === 'pdf' && selected.length > 12)
                                        ? 'cursor-not-allowed bg-gray-300 text-gray-600'
                                        : 'bg-indigo-600 text-white hover:bg-indigo-700'
                                "
                            >
                                Export data
                            </button>
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
