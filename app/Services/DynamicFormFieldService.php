<?php

namespace App\Services;

use Illuminate\Support\Str;
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

                case 'number':
                    $fieldRules[] = 'numeric';
                    break;

                case 'date':
                    $fieldRules[] = 'date';
                    break;

                case 'checkbox':
                    $fieldRules[] = 'boolean';
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
                        $fieldRules[] =
                            'in:'.implode(
                                ',',
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
