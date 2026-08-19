@props([
    'fields' => [],
])

@php
    $initialFields =
        is_array($fields)
            ? array_values($fields)
            : [];

    $oldFieldsJson =
        old('additional_fields_json');

    if (is_string($oldFieldsJson)) {
        $decodedOldFields =
            json_decode(
                $oldFieldsJson,
                true
            );

        if (is_array($decodedOldFields)) {
            $initialFields =
                array_values(
                    $decodedOldFields
                );
        }
    }
@endphp

<div
    class="mb-10"
    x-data="{
        fields: @js($initialFields),

        init() {
            this.fields =
                this.fields.map(
                    (field) =>
                        this.normalizeField(field)
                );
        },

        supportedTypes() {
            return [
                'text',
                'textarea',
                'email',
                'phone',
                'number',
                'date',
                'yes_no',
                'checkbox',
                'radio',
                'checkboxes',
                'select'
            ];
        },

        choiceTypes() {
            return [
                'radio',
                'checkboxes',
                'select'
            ];
        },

        newId() {
            if (
                window.crypto
                && typeof window.crypto.randomUUID === 'function'
            ) {
                return window.crypto.randomUUID();
            }

            return 'field-'
                + Date.now()
                + '-'
                + Math.random()
                    .toString(36)
                    .slice(2);
        },

        normalizeField(field) {
            field =
                field
                && typeof field === 'object'
                    ? field
                    : {};

            const type =
                this.supportedTypes()
                    .includes(field.type)
                    ? field.type
                    : 'text';

            return {
                id:
                    typeof field.id === 'string'
                    && field.id !== ''
                        ? field.id
                        : this.newId(),

                type: type,

                label:
                    typeof field.label === 'string'
                        ? field.label
                        : '',

                required:
                    Boolean(field.required),

                options:
                    this.choiceTypes()
                        .includes(type)
                        && Array.isArray(field.options)
                            ? field.options.map(
                                (option) =>
                                    String(option)
                            )
                            : []
            };
        },

        addQuestion() {
            this.fields.push(
                this.normalizeField({
                    type: 'text',
                    label: '',
                    required: false,
                    options: []
                })
            );
        },

        removeQuestion(index) {
            this.fields.splice(
                index,
                1
            );
        },

        moveQuestion(index, offset) {
            const target =
                index + offset;

            if (
                target < 0
                || target >= this.fields.length
            ) {
                return;
            }

            const current =
                this.fields[index];

            this.fields[index] =
                this.fields[target];

            this.fields[target] =
                current;
        },

        typeHasOptions(type) {
            return this.choiceTypes()
                .includes(type);
        },

        changeType(field) {
            if (
                ! this.typeHasOptions(
                    field.type
                )
            ) {
                field.options = [];
                return;
            }

            if (
                ! Array.isArray(
                    field.options
                )
            ) {
                field.options = [];
            }

            while (
                field.options.length < 2
            ) {
                field.options.push('');
            }
        },

        addOption(field) {
            if (
                ! Array.isArray(
                    field.options
                )
            ) {
                field.options = [];
            }

            field.options.push('');
        },

        removeOption(
            field,
            optionIndex
        ) {
            if (
                ! Array.isArray(
                    field.options
                )
                || field.options.length <= 2
            ) {
                return;
            }

            field.options.splice(
                optionIndex,
                1
            );
        }
    }"
>
    <input
        type="hidden"
        name="additional_fields_json"
        :value="JSON.stringify(fields)"
    >

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">
                Information to collect
            </h2>

            <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">
                Add questions when you need information from the signer beyond the standard signing details.
            </p>
        </div>

        <button
            type="button"
            @click="addQuestion()"
            class="inline-flex shrink-0 items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
            + Add question
        </button>
    </div>

    @error('additional_fields_json')
        <p class="mb-4 text-sm font-medium text-red-600">
            {{ $message }}
        </p>
    @enderror

    <div class="space-y-5">
        <template
            x-for="(field, index) in fields"
            :key="field.id"
        >
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 sm:p-6">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-gray-500">
                            Question
                        </p>

                        <h3
                            class="mt-1 text-lg font-semibold text-gray-900"
                            x-text="'Question ' + (index + 1)"
                        ></h3>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="moveQuestion(index, -1)"
                            :disabled="index === 0"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            Move up
                        </button>

                        <button
                            type="button"
                            @click="moveQuestion(index, 1)"
                            :disabled="index === fields.length - 1"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            Move down
                        </button>

                        <button
                            type="button"
                            @click="removeQuestion(index)"
                            class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-800"
                            :for="'question-label-' + field.id"
                        >
                            Question
                        </label>

                        <input
                            type="text"
                            x-model="field.label"
                            :id="'question-label-' + field.id"
                            required
                            placeholder="What information should the signer provide?"
                            class="mt-2 block w-full rounded-lg border-gray-300 p-3 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </div>

                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-800"
                            :for="'question-type-' + field.id"
                        >
                            Answer type
                        </label>

                        <select
                            x-model="field.type"
                            @change="changeType(field)"
                            :id="'question-type-' + field.id"
                            class="mt-2 block w-full rounded-lg border-gray-300 p-3 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="text">
                                Short answer
                            </option>

                            <option value="textarea">
                                Paragraph
                            </option>

                            <option value="email">
                                Email
                            </option>

                            <option value="phone">
                                Phone number
                            </option>

                            <option value="number">
                                Number
                            </option>

                            <option value="date">
                                Date
                            </option>

                            <option value="yes_no">
                                Yes / No
                            </option>

                            <option
                                value="checkbox"
                                :hidden="field.type !== 'checkbox'"
                            >
                                Existing acknowledgement
                            </option>

                            <option value="radio">
                                Multiple choice
                            </option>

                            <option value="checkboxes">
                                Checkboxes
                            </option>

                            <option value="select">
                                Dropdown
                            </option>
                        </select>
                    </div>
                </div>

                <template x-if="typeHasOptions(field.type)">
                    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h4 class="font-semibold text-gray-900">
                                    Answer options
                                </h4>

                                <p class="mt-1 text-sm text-gray-500">
                                    Add at least two choices for this question.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="addOption(field)"
                                class="shrink-0 text-sm font-semibold text-blue-600 hover:text-blue-800"
                            >
                                + Add option
                            </button>
                        </div>

                        <div class="mt-4 space-y-3">
                            <template
                                x-for="(option, optionIndex) in field.options"
                                :key="field.id + '-option-' + optionIndex"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        type="text"
                                        x-model="field.options[optionIndex]"
                                        required
                                        :placeholder="'Option ' + (optionIndex + 1)"
                                        class="block min-w-0 flex-1 rounded-lg border-gray-300 p-3 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >

                                    <button
                                        type="button"
                                        @click="removeOption(field, optionIndex)"
                                        :disabled="field.options.length <= 2"
                                        class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:text-gray-300 disabled:hover:bg-transparent"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="mt-6 border-t border-gray-200 pt-4">
                    <label class="inline-flex cursor-pointer items-center gap-3">
                        <input
                            type="checkbox"
                            x-model="field.required"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >

                        <span class="text-sm font-semibold text-gray-800">
                            Required
                        </span>
                    </label>
                </div>
            </div>
        </template>

        <div
            x-show="fields.length === 0"
            class="rounded-xl border-2 border-dashed border-gray-300 px-6 py-10 text-center"
        >
            <h3 class="font-semibold text-gray-800">
                No questions added yet
            </h3>

            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-gray-500">
                You can publish a consent without extra questions, or add a question when you need structured information from the signer.
            </p>
        </div>
    </div>
</div>
