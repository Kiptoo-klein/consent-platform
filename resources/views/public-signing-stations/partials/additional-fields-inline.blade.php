{{-- KIOSK_SELECTED_FIELDS_INLINE --}}
@if (count($additionalFields) > 0)
    @foreach ($additionalFields as $index => $field)
        @php
            $fieldKey =
                $field['name']
                ?? $field['key']
                ?? $field['id']
                ?? 'field_'.$index;

            $fieldType = $field['type'] ?? 'text';
            $fieldLabel = $field['label'] ?? 'Field '.($index + 1);
            $fieldDescription = $field['description'] ?? null;
            $fieldValue = $existingResponses[$fieldKey] ?? null;
            $isRequired = (bool) ($field['required'] ?? false);
            $fieldOptions = $field['options'] ?? [];
        @endphp

        <div>
            <div class="flex items-center justify-between gap-3">
                <label
                    for="response_{{ $fieldKey }}"
                    class="block text-sm font-bold text-slate-800"
                >
                    {{ $fieldLabel }}

                    @if ($isRequired)
                        <span class="text-red-500">*</span>
                    @endif
                </label>

                @unless ($isRequired)
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">
                        Optional
                    </span>
                @endunless
            </div>

            @if ($fieldDescription && $fieldType !== 'checkbox')
                <p class="mt-1 text-sm leading-6 text-slate-500">
                    {{ $fieldDescription }}
                </p>
            @endif

            @if ($fieldType === 'textarea')
                <textarea
                    id="response_{{ $fieldKey }}"
                    name="responses[{{ $fieldKey }}]"
                    rows="4"
                    @required($isRequired)
                    class="station-primary-ring mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400"
                >{{ $fieldValue }}</textarea>

            @elseif ($fieldType === 'select')
                <select
                    id="response_{{ $fieldKey }}"
                    name="responses[{{ $fieldKey }}]"
                    @required($isRequired)
                    class="station-primary-ring mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-950 shadow-sm outline-none transition hover:border-slate-400"
                >
                    <option value="">Select an option</option>

                    @foreach ($fieldOptions as $option)
                        <option
                            value="{{ $option }}"
                            @selected((string) $fieldValue === (string) $option)
                        >
                            {{ $option }}
                        </option>
                    @endforeach
                </select>

            @elseif ($fieldType === 'radio')
                <div class="mt-3 space-y-3">
                    @foreach ($fieldOptions as $option)
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-slate-300">
                            <input
                                type="radio"
                                name="responses[{{ $fieldKey }}]"
                                value="{{ $option }}"
                                @checked((string) $fieldValue === (string) $option)
                                @required($isRequired)
                                class="station-primary-ring mt-1 h-4 w-4 border-slate-300"
                            >

                            <span class="text-sm font-medium text-slate-700">
                                {{ $option }}
                            </span>
                        </label>
                    @endforeach
                </div>

            @elseif ($fieldType === 'checkbox')
                <input
                    type="hidden"
                    name="responses[{{ $fieldKey }}]"
                    value="0"
                >

                <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:border-slate-300">
                    <input
                        id="response_{{ $fieldKey }}"
                        type="checkbox"
                        name="responses[{{ $fieldKey }}]"
                        value="1"
                        @checked((bool) $fieldValue)
                        @required($isRequired)
                        class="station-primary-ring mt-1 h-5 w-5 rounded border-slate-300"
                    >

                    <span class="text-sm leading-6 text-slate-700">
                        {{ $fieldDescription ?? $fieldLabel }}
                    </span>
                </label>

            @else
                <input
                    id="response_{{ $fieldKey }}"
                    name="responses[{{ $fieldKey }}]"
                    type="{{ in_array($fieldType, ['email', 'number', 'date'], true) ? $fieldType : 'text' }}"
                    value="{{ $fieldValue }}"
                    @required($isRequired)
                    class="station-primary-ring mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400"
                >
            @endif

            @error("responses.{$fieldKey}")
                <p class="mt-2 text-sm font-medium text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>
    @endforeach
@endif
