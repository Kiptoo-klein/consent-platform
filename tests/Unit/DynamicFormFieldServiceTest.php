<?php

namespace Tests\Unit;

use App\Services\DynamicFormFieldService;
use Illuminate\Support\Facades\Validator;
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
            'required',
            $rules['responses.department'][0]
        );

        $this->assertSame(
            'string',
            $rules['responses.department'][1]
        );

        $this->assertStringStartsWith(
            'in:',
            (string) $rules[
                'responses.department'
            ][2]
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
    public function test_it_normalizes_typed_data_collection_fields(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $fields = $service->normalizeTypedJson(
            json_encode([
                [
                    'id' => 'employee-name',
                    'type' => 'text',
                    'label' => ' Employee Name ',
                    'required' => true,
                ],
                [
                    'id' => 'department',
                    'type' => 'select',
                    'label' => 'Department',
                    'required' => false,
                    'options' => [
                        ' Finance ',
                        'IT',
                        'Finance',
                        '',
                    ],
                ],
                [
                    'id' => 'comments',
                    'type' => 'textarea',
                    'label' => 'Comments',
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $this->assertSame([
            [
                'id' => 'employee-name',
                'type' => 'text',
                'label' => 'Employee Name',
                'required' => true,
                'options' => [],
            ],
            [
                'id' => 'department',
                'type' => 'select',
                'label' => 'Department',
                'required' => false,
                'options' => [
                    'Finance',
                    'IT',
                ],
            ],
            [
                'id' => 'comments',
                'type' => 'textarea',
                'label' => 'Comments',
                'required' => false,
                'options' => [],
            ],
        ], $fields);
    }

    public function test_typed_fields_default_to_text(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $fields =
            $service->normalizeTypedDefinitions([
                [
                    'id' => 'reference',
                    'label' => 'Reference',
                ],
            ]);

        $this->assertSame(
            'text',
            $fields[0]['type']
        );

        $this->assertSame(
            [],
            $fields[0]['options']
        );
    }

    public function test_it_rejects_unsupported_typed_fields(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $this->expectException(
            ValidationException::class
        );

        $service->normalizeTypedDefinitions([
            [
                'label' => 'Attachment',
                'type' => 'file',
            ],
        ]);
    }

    public function test_select_fields_require_at_least_two_options(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $this->expectException(
            ValidationException::class
        );

        $service->normalizeTypedDefinitions([
            [
                'label' => 'Department',
                'type' => 'select',
                'options' => [
                    'Finance',
                ],
            ],
        ]);
    }

    public function test_existing_consent_normalization_still_strips_typed_metadata(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $fields = $service->normalizeDefinitions([
            [
                'id' => 'legacy-field',
                'label' => 'Legacy field',
                'required' => true,
                'type' => 'select',
                'options' => [
                    'One',
                    'Two',
                ],
            ],
        ]);

        $this->assertSame([
            [
                'id' => 'legacy-field',
                'label' => 'Legacy field',
                'required' => true,
            ],
        ], $fields);
    }

    public function test_typed_field_ids_must_be_unique(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $this->expectException(
            ValidationException::class
        );

        $service->normalizeTypedDefinitions([
            [
                'id' => 'employee-number',
                'label' => 'Employee Number',
            ],
            [
                'id' => 'employee-number',
                'label' => 'Second Employee Number',
            ],
        ]);
    }

    public function test_typed_field_ids_must_be_validation_safe(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $this->expectException(
            ValidationException::class
        );

        $service->normalizeTypedDefinitions([
            [
                'id' => 'employee.number',
                'label' => 'Employee Number',
            ],
        ]);
    }

    public function test_select_options_may_contain_commas(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $rules = $service->validationRules([
            [
                'id' => 'location',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'Nairobi, Kenya',
                    'Mombasa, Kenya',
                ],
            ],
        ]);

        $valid = Validator::make([
            'responses' => [
                'location' => 'Nairobi, Kenya',
            ],
        ], $rules);

        $invalid = Validator::make([
            'responses' => [
                'location' => 'Kisumu, Kenya',
            ],
        ], $rules);

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalid->fails());
    }

    public function test_phone_and_checkbox_group_fields_are_supported(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $fields =
            $service->normalizeTypedDefinitions([
                [
                    'id' => 'contact-phone',
                    'type' => 'phone',
                    'label' => 'Phone Number',
                    'required' => true,
                ],
                [
                    'id' => 'contact-methods',
                    'type' => 'checkboxes',
                    'label' => 'Preferred Contact Methods',
                    'required' => true,
                    'options' => [
                        ' Email ',
                        'SMS',
                        'Email',
                    ],
                ],
            ]);

        $this->assertSame(
            [
                [
                    'id' => 'contact-phone',
                    'type' => 'phone',
                    'label' => 'Phone Number',
                    'required' => true,
                    'options' => [],
                ],
                [
                    'id' => 'contact-methods',
                    'type' => 'checkboxes',
                    'label' => 'Preferred Contact Methods',
                    'required' => true,
                    'options' => [
                        'Email',
                        'SMS',
                    ],
                ],
            ],
            $fields
        );

        $rules = $service->validationRules(
            $fields
        );

        $this->assertSame(
            [
                'required',
                'string',
                'max:50',
            ],
            $rules['responses.contact-phone']
        );

        $this->assertSame(
            [
                'required',
                'array',
                'min:1',
            ],
            $rules['responses.contact-methods']
        );

        $valid = Validator::make([
            'responses' => [
                'contact-phone' =>
                    '+254 712 345 678',
                'contact-methods' => [
                    'Email',
                    'SMS',
                ],
            ],
        ], $rules);

        $unknownOption = Validator::make([
            'responses' => [
                'contact-phone' =>
                    '+254 712 345 678',
                'contact-methods' => [
                    'Carrier pigeon',
                ],
            ],
        ], $rules);

        $emptyRequiredGroup = Validator::make([
            'responses' => [
                'contact-phone' =>
                    '+254 712 345 678',
                'contact-methods' => [],
            ],
        ], $rules);

        $this->assertFalse(
            $valid->fails()
        );

        $this->assertTrue(
            $unknownOption->fails()
        );

        $this->assertTrue(
            $emptyRequiredGroup->fails()
        );
    }


    public function test_yes_no_fields_are_supported_as_explicit_boolean_answers(): void
    {
        $service =
            app(DynamicFormFieldService::class);

        $fields =
            $service->normalizeTypedDefinitions([
                [
                    'id' =>
                        'approved',

                    'type' =>
                        'yes_no',

                    'label' =>
                        'Do you approve?',

                    'required' =>
                        true,
                ],
            ]);

        $this->assertSame(
            [
                [
                    'id' =>
                        'approved',

                    'type' =>
                        'yes_no',

                    'label' =>
                        'Do you approve?',

                    'required' =>
                        true,

                    'options' =>
                        [],
                ],
            ],
            $fields
        );

        $rules =
            $service->validationRules(
                $fields
            );

        $this->assertSame(
            [
                'required',
                'boolean',
            ],
            $rules['responses.approved']
        );

        $yes = Validator::make([
            'responses' => [
                'approved' =>
                    '1',
            ],
        ], $rules);

        $no = Validator::make([
            'responses' => [
                'approved' =>
                    '0',
            ],
        ], $rules);

        $invalid = Validator::make([
            'responses' => [
                'approved' =>
                    'maybe',
            ],
        ], $rules);

        $this->assertFalse(
            $yes->fails()
        );

        $this->assertFalse(
            $no->fails()
        );

        $this->assertTrue(
            $invalid->fails()
        );
    }

}
