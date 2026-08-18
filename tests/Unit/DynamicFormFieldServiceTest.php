<?php

namespace Tests\Unit;

use App\Services\DynamicFormFieldService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DynamicFormFieldServiceTest extends TestCase
{
    public function test_it_normalizes_current_consent_fields(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $fields = $service->normalizeJson(
            json_encode([
                [
                    'id' => 'employee-number',
                    'label' => ' Employee Number ',
                    'required' => true,
                ],
                [
                    'id' => 'department',
                    'label' => 'Department',
                    'required' => false,
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame([
            [
                'id' => 'employee-number',
                'label' => 'Employee Number',
                'required' => true,
            ],
            [
                'id' => 'department',
                'label' => 'Department',
                'required' => false,
            ],
        ], $fields);
    }

    public function test_it_generates_an_id_when_missing(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $fields = $service->normalizeJson(
            json_encode([
                [
                    'label' => 'Reference',
                    'required' => false,
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertCount(1, $fields);
        $this->assertNotSame('', $fields[0]['id']);
        $this->assertSame(
            'Reference',
            $fields[0]['label']
        );
        $this->assertFalse(
            $fields[0]['required']
        );
    }

    public function test_it_rejects_fields_without_labels(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $this->expectException(
            ValidationException::class
        );

        $service->normalizeJson(
            json_encode([
                [
                    'id' => 'missing-label',
                    'label' => '   ',
                ],
            ], JSON_THROW_ON_ERROR)
        );
    }

    public function test_it_reads_fields_from_template_schema(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $fields = $service->fieldsFromSchema([
            'additional_fields' => [
                [
                    'id' => 'name',
                    'label' => 'Name',
                ],
                'invalid',
                [
                    'id' => 'email',
                    'label' => 'Email',
                ],
            ],
        ]);

        $this->assertCount(2, $fields);
        $this->assertSame(
            'name',
            $fields[0]['id']
        );
        $this->assertSame(
            'email',
            $fields[1]['id']
        );
    }

    public function test_it_builds_typed_response_validation_rules(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $rules = $service->validationRules([
            [
                'id' => 'full-name',
                'required' => true,
            ],
            [
                'id' => 'contact-email',
                'type' => 'email',
                'required' => false,
            ],
            [
                'id' => 'age',
                'type' => 'number',
                'required' => false,
            ],
            [
                'id' => 'department',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'Finance',
                    'IT',
                ],
            ],
            [
                'id' => 'comments',
                'type' => 'textarea',
                'required' => false,
            ],
        ]);

        $this->assertSame(
            ['required', 'string', 'max:1000'],
            $rules['responses.full-name']
        );

        $this->assertSame(
            ['nullable', 'email', 'max:255'],
            $rules['responses.contact-email']
        );

        $this->assertSame(
            ['nullable', 'numeric'],
            $rules['responses.age']
        );

        $this->assertSame(
            [
                'required',
                'string',
                'in:Finance,IT',
            ],
            $rules['responses.department']
        );

        $this->assertSame(
            [
                'nullable',
                'string',
                'max:5000',
            ],
            $rules['responses.comments']
        );
    }

    public function test_field_key_uses_existing_legacy_fallback_order(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $this->assertSame(
            'name-value',
            $service->fieldKey([
                'name' => 'name-value',
                'key' => 'key-value',
                'id' => 'id-value',
            ], 0)
        );

        $this->assertSame(
            'key-value',
            $service->fieldKey([
                'key' => 'key-value',
                'id' => 'id-value',
            ], 0)
        );

        $this->assertSame(
            'id-value',
            $service->fieldKey([
                'id' => 'id-value',
            ], 0)
        );

        $this->assertSame(
            'field_4',
            $service->fieldKey([], 4)
        );
    }
}
