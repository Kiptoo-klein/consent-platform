<?php

namespace App\Http\Controllers;

use App\Models\ConsentNotification;
use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Services\ConsentAuditService;
use App\Services\ConsentNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class IndividualConsentWizardController extends Controller
{
    /**
     * Build a new individual-consent template and its first record together.
     */
    public function create(): View
    {
        return view('consent-templates.create-individual-wizard');
    }

    /**
     * Publish a new individual template, create the signer record,
     * then continue to the existing sharing/evidence screen.
     */
    public function store(
        Request $request,
        ConsentAuditService $consentAuditService,
        ConsentNotificationService $consentNotificationService
    ): RedirectResponse {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'content' => ['required', 'string'],
            'additional_fields_json' => ['required', 'string'],

            'signer_name' => ['required', 'string', 'max:255'],
            'signer_email' => ['nullable', 'email', 'max:255'],

            'expires_date' => [
                'nullable',
                'required_with:expires_time',
                'date_format:Y-m-d',
            ],
            'expires_time' => [
                'nullable',
                Rule::prohibitedIf(fn (): bool => blank($request->input('expires_date'))),'nullable', 'date_format:H:i'],
        ], [
            'expires_date.required_with' =>
                'Please select the signing deadline date.',
            'expires_date.date_format' =>
                'Please select a valid signing deadline date.',

            'expires_time.prohibited' =>
                'Select a deadline date before choosing a deadline time.',

            'expires_time.date_format' =>
                'Please enter a valid signing deadline time.',
        ]);

        $additionalFields = $this->prepareAdditionalFields(
            $validated['additional_fields_json']
        );

        $expiresAt = null;

        if (filled($validated['expires_date'] ?? null)) {
            $expiresTime = $validated['expires_time'] ?? '00:00';

            $expiresAt = CarbonImmutable::createFromFormat(
                '!Y-m-d H:i',
                $validated['expires_date'].' '.$expiresTime,
                config('app.timezone')
            );

            if (! $expiresAt->isAfter(now())) {
                throw ValidationException::withMessages([
                    'expires_date' =>
                        'The signing deadline must be in the future.',
                ]);
            }
        }

        $result = DB::transaction(
            function () use (
                $validated,
                $additionalFields,
                $expiresAt,
                $request,
                $consentAuditService
            ): array {
                $templateSchema = [
                    'builder_version' => 1,
                    'consent_text' => $validated['content'],
                    'additional_fields' => $additionalFields,
                ];

                $consentTemplate = ConsentTemplate::query()->create([
                    'organization_id' => Auth::user()->organization_id,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'usage_type' => ConsentTemplate::USAGE_INDIVIDUAL,
                    'template_schema' => $templateSchema,
                    'active_version_id' => null,
                    'has_unpublished_changes' => true,
                    'status' => 'draft',
                ]);

                $publishedVersion = $consentTemplate
                    ->versions()
                    ->create([
                        'version_number' => 1,
                        'title' => $consentTemplate->title,
                        'description' => $consentTemplate->description,
                        'template_schema' => $templateSchema,
                        'published_at' => now(),
                        'published_by' => Auth::id(),
                    ]);

                $consentTemplate->update([
                    'active_version_id' => $publishedVersion->id,
                    'has_unpublished_changes' => false,
                    'status' => 'published',
                ]);

                $consentSession = ConsentSession::query()->create([
                    'organization_id' => Auth::user()->organization_id,
                    'consent_template_id' => $consentTemplate->id,
                    'consent_template_version_id' => $publishedVersion->id,
                    'created_by' => Auth::id(),
                    'signer_name' => $validated['signer_name'],
                    'signer_email' => $validated['signer_email'] ?? null,
                    'signer_reference' => null,
                    'access_token' => (string) Str::uuid(),
                    'status' => ConsentSession::STATUS_PENDING,
                    'responses' => [],
                    'expires_at' => $expiresAt,
                    'expired_at' => null,
                ]);

                $consentAuditService->record(
                    consentSession: $consentSession,
                    eventType: 'consent.created',
                    description:
                        'A new individual template was published and its first consent record was created.',
                    metadata: [
                        'creation_source' =>
                            'individual_consent_template_wizard',
                        'consent_template_id' => $consentTemplate->id,
                        'consent_template_version_id' =>
                            $publishedVersion->id,
                        'initial_status' => ConsentSession::STATUS_PENDING,
                        'expires_at' => $expiresAt?->toIso8601String(),
                    ],
                    request: $request
                );

                return [
                    'template' => $consentTemplate,
                    'session' => $consentSession,
                ];
            },
            3
        );

        /** @var ConsentSession $consentSession */
        $consentSession = $result['session'];
        $emailDelivery = null;

        if (filled($consentSession->signer_email)) {
            $emailDelivery = $consentNotificationService->sendInitial(
                consentSession: $consentSession,
                actorUserId: Auth::id(),
                trigger: ConsentNotification::TRIGGER_AUTOMATIC_CREATION
            );
        }

        $redirect = redirect()
            ->route('consent-sessions.show', $consentSession)
            ->with(
                'success',
                'The individual consent template was published and the signer record was created. Use the sharing options below.'
            );

        if ($emailDelivery?->isSent()) {
            $redirect->with(
                'email_success',
                "The secure signing link was emailed to {$consentSession->signer_email}."
            );
        } elseif ($emailDelivery?->isFailed()) {
            $redirect->with(
                'email_warning',
                'The consent record was created, but the signing email could not be delivered. Use Send email again after checking the mail settings.'
            );
        }

        return $redirect;
    }

    /**
     * Decode and validate dynamic fields from the template builder.
     */
    private function prepareAdditionalFields(string $fieldsJson): array
    {
        try {
            $additionalFields = json_decode(
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

        if (! is_array($additionalFields)) {
            throw ValidationException::withMessages([
                'additional_fields_json' =>
                    'The additional fields must be a valid list.',
            ]);
        }

        $cleanFields = [];

        foreach ($additionalFields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $label = trim((string) ($field['label'] ?? ''));

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

            $fieldId = trim((string) ($field['id'] ?? ''));

            if ($fieldId === '') {
                $fieldId = (string) Str::uuid();
            }

            $cleanFields[] = [
                'id' => $fieldId,
                'label' => $label,
                'required' => (bool) ($field['required'] ?? false),
            ];
        }

        return $cleanFields;
    }
}
