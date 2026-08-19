<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JsonException;

final class DynamicFormFieldService
{
    public function normalizeJson(string $fieldsJson): array
    {
        try {
            $fields = json_decode(
                $fieldsJson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'additional_fields_json' =>
                    'The additional fields could not be processed.',
            ]);
        }

        if (! is_array($fields)) {
            throw ValidationException::withMessages([
                'additional_fields_json' =>
                    'The additional fields must be a valid list.',
            ]);
        }

        return $this->normalizeDefinitions($fields);
    }

    public function normalizeDefinitions(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $label = trim(
                (string) ($field['label'] ?? '')
            );

            if ($label === '') {
                throw ValidationException::withMessages([
                    'additional_fields_json' =>
                        'Every additional field must have a label.',
                ]);
            }

            if (mb_strlen($label) > 255) {
                throw ValidationException::withMessages([
                    'additional_fields_json' =>
                        'Additional field labels cannot exceed 255 characters.',
                ]);
            }

            $fieldId = trim(
                (string) ($field['id'] ?? '')
            );

            if ($fieldId === '') {
                $fieldId = (string) Str::uuid();
            }

            $normalized[] = [
                'id' => $fieldId,
                'label' => $label,
                'required' =>
                    (bool) ($field['required'] ?? false),
            ];
        }

        return $normalized;
    }

    /**
     * Decode and normalize typed dynamic fields.
     *
     * Used by richer form builders such as Data Collection.
     * Existing Consent normalization intentionally remains unchanged.
     */
    public function normalizeTypedJson(string $fieldsJson): array
    {
        try {
            $fields = json_decode(
                $fieldsJson,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'additional_fields_json' =>
                    'The additional fields could not be processed.',
            ]);
        }

        if (! is_array($fields)) {
            throw ValidationException::withMessages([
                'additional_fields_json' =>
                    'The additional fields must be a valid list.',
            ]);
        }

        return $this->normalizeTypedDefinitions($fields);
    }

    /**
     * Normalize richer dynamic form field definitions.
     */
    public function normalizeTypedDefinitions(array $fields): array
    {
        $allowedTypes = [
            'text',
            'textarea',
            'email',
            'phone',
            'number',
            'date',
            'checkbox',
            'checkboxes',
            'select',
            'radio',
        ];

        $normalized = [];
        $seenIds = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $label = trim(
                (string) ($field['label'] ?? '')
            );

            if ($label === '') {
                throw ValidationException::withMessages([
                    'additional_fields_json' =>
                        'Every additional field must have a label.',
                ]);
            }

            if (mb_strlen($label) > 255) {
                throw ValidationException::withMessages([
                    'additional_fields_json' =>
                        'Additional field labels cannot exceed 255 characters.',
                ]);
            }

            $fieldId = trim(
                (string) ($field['id'] ?? '')
            );

            if ($fieldId === '') {
                $fieldId = (string) Str::uuid();
            }

            if (
                mb_strlen($fieldId) > 100
                || preg_match(
                    '/\A[A-Za-z0-9_-]+\z/',
                    $fieldId
                ) !== 1
            ) {
                throw ValidationException::withMessages([
                    'additional_fields_json' =>
                        'Field identifiers may contain only letters, numbers, hyphens, and underscores.',
                ]);
            }

            if (isset($seenIds[$fieldId])) {
                throw ValidationException::withMessages([
                    'additional_fields_json' =>
                        'Every additional field must have a unique identifier.',
                ]);
            }

            $seenIds[$fieldId] = true;

            $type = strtolower(
                trim(
                    (string) ($field['type'] ?? 'text')
                )
            );

            if (! in_array($type, $allowedTypes, true)) {
                throw ValidationException::withMessages([
                    'additional_fields_json' =>
                        "The field type '{$type}' is not supported.",
                ]);
            }

            $options = [];

            if (
                in_array(
                    $type,
                    [
                        'select',
                        'radio',
                        'checkboxes',
                    ],
                    true
                )
            ) {
                $rawOptions = $field['options'] ?? [];

                if (! is_array($rawOptions)) {
                    throw ValidationException::withMessages([
                        'additional_fields_json' =>
                            'Choice fields must provide a valid options list.',
                    ]);
                }

                foreach ($rawOptions as $option) {
                    if (! is_scalar($option)) {
                        continue;
                    }

                    $value = trim((string) $option);

                    if ($value === '') {
                        continue;
                    }

                    if (mb_strlen($value) > 255) {
                        throw ValidationException::withMessages([
                            'additional_fields_json' =>
                                'Field options cannot exceed 255 characters.',
                        ]);
                    }

                    if (! in_array($value, $options, true)) {
                        $options[] = $value;
                    }
                }

                if (count($options) < 2) {
                    throw ValidationException::withMessages([
                        'additional_fields_json' =>
                            'Choice fields require at least two options.',
                    ]);
                }
            }

            $normalized[] = [
                'id' => $fieldId,
                'type' => $type,
                'label' => $label,
                'required' =>
                    (bool) ($field['required'] ?? false),
                'options' => $options,
            ];
        }

        return $normalized;
    }

    public function fieldsFromSchema(?array $schema): array
    {
        $fields = data_get(
            $schema,
            'additional_fields',
            []
        );

        if (! is_array($fields)) {
            return [];
        }

        return array_values(
            array_filter(
                $fields,
                fn ($field): bool =>
                    is_array($field)
            )
        );
    }

    public function validationRules(array $fields): array
    {
        $rules = [
            'responses' => [
                'nullable',
                'array',
            ],
        ];

        foreach ($fields as $index => $field) {
            $key = $this->fieldKey(
                $field,
                $index
            );

            $fieldRules = [];

            $fieldRules[] =
                ($field['required'] ?? false)
                    ? 'required'
                    : 'nullable';

            $type = $field['type'] ?? 'text';

            switch ($type) {
                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:255';
                    break;

                case 'phone':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:50';
                    break;

                case 'number':
                    $fieldRules[] = 'numeric';
                    break;

                case 'date':
                    $fieldRules[] = 'date';
                    break;

                case 'checkbox':
                    $fieldRules[] = 'boolean';
                    break;

                case 'checkboxes':
                    $fieldRules[] = 'array';

                    if ($field['required'] ?? false) {
                        $fieldRules[] = 'min:1';
                    }

                    $options =
                        $field['options'] ?? [];

                    $itemRules = [
                        'string',
                    ];

                    if (
                        is_array($options)
                        && $options !== []
                    ) {
                        $itemRules[] = Rule::in(
                            array_map(
                                'strval',
                                $options
                            )
                        );
                    }

                    $rules[
                        "responses.{$key}.*"
                    ] = $itemRules;

                    break;

                case 'select':
                case 'radio':
                    $fieldRules[] = 'string';

                    $options =
                        $field['options'] ?? [];

                    if (
                        is_array($options)
                        && $options !== []
                    ) {
                        $fieldRules[] = Rule::in(
                            array_map(
                                'strval',
                                $options
                            )
                        );
                    }

                    break;

                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:5000';
                    break;

                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:1000';
                    break;
            }

            $rules["responses.{$key}"] =
                $fieldRules;
        }

        return $rules;
    }

    public function fieldKey(
        array $field,
        int $index
    ): string {
        return (string) (
            $field['name']
            ?? $field['key']
            ?? $field['id']
            ?? 'field_'.$index
        );
    }
}
