<?php

namespace App\Http\Controllers;

use App\Models\ConsentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;

class ConsentTemplateController extends Controller
{
    /**
     * Display the organization's existing consent templates by default.
     */
    public function index(): View
    {
        return $this->manage();
    }

    /**
     * Display the consent-type chooser before the template builder.
     */
    public function chooser(): View
    {
        return view('consent-templates.index');
    }

    /**
     * Display the organization's existing consent templates.
     */
    public function manage(): View
    {
        $consentTemplates = ConsentTemplate::query()
            ->where(
                'organization_id',
                Auth::user()->organization_id
            )
            ->with([
                'activeVersion.publisher',
                'latestVersion',
            ])
            ->latest()
            ->get();

        return view('consent-templates.manage', [
            'consentTemplates' => $consentTemplates,
        ]);
    }

    /**
     * Show the template builder for the selected consent workflow.
     */
    public function create(
        Request $request
    ): View|RedirectResponse {
        $selectedUsageType = (string) $request->query('type', '');

        if (! in_array(
            $selectedUsageType,
            [
                ConsentTemplate::USAGE_INDIVIDUAL,
                ConsentTemplate::USAGE_SIGNING_STATION,
            ],
            true
        )) {
            return redirect()
                ->route('consent-templates.new')
                ->withErrors([
                    'template_type' =>
                        'Choose Individual consent or Public consent before opening the template builder.',
                ]);
        }

        return view('consent-templates.create', [
            'selectedUsageType' => $selectedUsageType,
        ]);
    }

    /**
     * Save a new working draft.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTemplateRequest($request);

        $additionalFields = $this->prepareAdditionalFields(
            $validated['additional_fields_json']
        );

        ConsentTemplate::create([
            'organization_id' => Auth::user()->organization_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'usage_type' => $this->templateUsageType(
                $validated['usage_types']
            ),

            'template_schema' => [
                'builder_version' => 1,
                'consent_text' => $validated['content'],
                'additional_fields' => $additionalFields,
            ],

            'active_version_id' => null,
            'has_unpublished_changes' => true,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('consent-templates.manage')
            ->with(
                'success',
                'Consent template saved as a working draft.'
            );
    }

    /**
     * Preview the editable working copy.
     *
     * This does not display or modify the immutable live version.
     */
    public function preview(
        ConsentTemplate $consentTemplate
    ): View {
        $this->ensureTemplateBelongsToOrganization(
            $consentTemplate
        );

        $consentTemplate->load([
            'activeVersion.publisher',
            'latestVersion',
        ]);

        return view('consent-templates.preview', [
            'consentTemplate' => $consentTemplate,
        ]);
    }

    /**
     * Display the active immutable published version.
     */
    public function showPublished(
        ConsentTemplate $consentTemplate
    ): View|RedirectResponse {
        $this->ensureTemplateBelongsToOrganization(
            $consentTemplate
        );

        $consentTemplate->load([
            'activeVersion.publisher',
        ]);

        if ($consentTemplate->activeVersion === null) {
            return redirect()
                ->route('consent-templates.manage')
                ->withErrors([
                    'template' =>
                        'This consent template has not been published yet.',
                ]);
        }

        return view('consent-templates.show-published', [
            'consentTemplate' => $consentTemplate,
            'publishedVersion' => $consentTemplate->activeVersion,
        ]);
    }

    /**
     * Display every immutable published version.
     */
    public function history(
        ConsentTemplate $consentTemplate
    ): View {
        $this->ensureTemplateBelongsToOrganization(
            $consentTemplate
        );

        $consentTemplate->load([
            'activeVersion',
            'versions.publisher',
        ]);

        return view('consent-templates.history', [
            'consentTemplate' => $consentTemplate,
            'versions' => $consentTemplate->versions,
        ]);
    }

    /**
     * Show the editable working copy.
     */
    public function edit(
        ConsentTemplate $consentTemplate
    ): View|RedirectResponse {
        $this->ensureTemplateBelongsToOrganization(
            $consentTemplate
        );

        if ($consentTemplate->status === 'archived') {
            return redirect()
                ->route('consent-templates.manage')
                ->withErrors([
                    'template' =>
                        'Archived templates cannot be edited.',
                ]);
        }

        return view('consent-templates.edit', [
            'consentTemplate' => $consentTemplate,
        ]);
    }

    /**
     * Update the editable working copy.
     *
     * The active published version remains unchanged until
     * the organization publishes the working copy.
     */
    public function update(
        Request $request,
        ConsentTemplate $consentTemplate
    ): RedirectResponse {
        $this->ensureTemplateBelongsToOrganization(
            $consentTemplate
        );

        if ($consentTemplate->status === 'archived') {
            return redirect()
                ->route('consent-templates.manage')
                ->withErrors([
                    'template' =>
                        'Archived templates cannot be edited.',
                ]);
        }

        $validated = $this->validateTemplateRequest($request);

        $additionalFields = $this->prepareAdditionalFields(
            $validated['additional_fields_json']
        );

        $usageType = $this->templateUsageType(
            $validated['usage_types']
        );

        if (
            ! $this->usageSupportsSigningStation($usageType)
            && $consentTemplate->signingStations()->exists()
        ) {
            throw ValidationException::withMessages([
                'usage_types' =>
                    'This template is assigned to one or more signing stations. Keep Public signing station selected or change those stations first.',
            ]);
        }

        $consentTemplate->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'usage_type' => $usageType,

            'template_schema' => [
                'builder_version' => 1,
                'consent_text' => $validated['content'],
                'additional_fields' => $additionalFields,
            ],

            'has_unpublished_changes' => true,
        ]);

        $message = $consentTemplate->active_version_id === null
            ? 'Working draft updated successfully.'
            : 'Working copy updated. The live version has not changed.';

        return redirect()
            ->route('consent-templates.manage')
            ->with('success', $message);
    }

    /**
     * Publish the working copy as a new immutable version.
     */
    public function publish(
        ConsentTemplate $consentTemplate
    ): RedirectResponse {
        $this->ensureTemplateBelongsToOrganization(
            $consentTemplate
        );

        $publishedVersionNumber = DB::transaction(
            function () use ($consentTemplate): int {
                $lockedTemplate = ConsentTemplate::query()
                    ->whereKey($consentTemplate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureTemplateBelongsToOrganization(
                    $lockedTemplate
                );

                if ($lockedTemplate->status === 'archived') {
                    throw ValidationException::withMessages([
                        'template' =>
                            'Archived templates cannot be published.',
                    ]);
                }

                /*
                 * An unchanged template that was taken offline should restore
                 * its latest immutable version instead of creating a duplicate.
                 */
                if (
                    ! $lockedTemplate->has_unpublished_changes
                    && $lockedTemplate->active_version_id === null
                ) {
                    $latestPublishedVersion = $lockedTemplate
                        ->versions()
                        ->orderByDesc('version_number')
                        ->first();

                    if ($latestPublishedVersion === null) {
                        throw ValidationException::withMessages([
                            'template' =>
                                'There is no published version to restore.',
                        ]);
                    }

                    $lockedTemplate->update([
                        'active_version_id' =>
                            $latestPublishedVersion->id,
                        'status' => 'published',
                    ]);

                    return (int)
                        $latestPublishedVersion->version_number;
                }

                if (
                    ! $lockedTemplate->has_unpublished_changes
                    && $lockedTemplate->active_version_id !== null
                ) {
                    throw ValidationException::withMessages([
                        'template' =>
                            'There are no unpublished changes to publish.',
                    ]);
                }

                $latestVersionNumber = $lockedTemplate
                    ->versions()
                    ->max('version_number');

                $nextVersionNumber =
                    ($latestVersionNumber ?? 0) + 1;

                $publishedVersion = $lockedTemplate
                    ->versions()
                    ->create([
                        'version_number' => $nextVersionNumber,
                        'title' => $lockedTemplate->title,
                        'description' => $lockedTemplate->description,
                        'template_schema' =>
                            $lockedTemplate->template_schema,
                        'published_at' => now(),
                        'published_by' => Auth::id(),
                    ]);

                $lockedTemplate->update([
                    'active_version_id' => $publishedVersion->id,
                    'has_unpublished_changes' => false,
                    'status' => 'published',
                ]);

                return $nextVersionNumber;
            },
            3
        );

        return redirect()
            ->route('consent-templates.manage')
            ->with(
                'success',
                "Version {$publishedVersionNumber} is now live."
            );
    }

    /**
     * Take the active published version offline without deleting history.
     */
    public function unpublish(
        ConsentTemplate $consentTemplate
    ): RedirectResponse {
        $this->ensureTemplateBelongsToOrganization(
            $consentTemplate
        );

        DB::transaction(
            function () use ($consentTemplate): void {
                $lockedTemplate = ConsentTemplate::query()
                    ->whereKey($consentTemplate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureTemplateBelongsToOrganization(
                    $lockedTemplate
                );

                if ($lockedTemplate->status === 'archived') {
                    throw ValidationException::withMessages([
                        'template' =>
                            'Archived templates cannot be unpublished.',
                    ]);
                }

                if ($lockedTemplate->active_version_id === null) {
                    throw ValidationException::withMessages([
                        'template' =>
                            'This template is already offline.',
                    ]);
                }

                $lockedTemplate->update([
                    'active_version_id' => null,
                    'status' => 'draft',
                ]);
            },
            3
        );

        return redirect()
            ->route('consent-templates.manage')
            ->with(
                'success',
                'The consent template is now offline.'
            );
    }

    /**
     * Archive a template without deleting its versions or records.
     */
    public function archive(
        ConsentTemplate $consentTemplate
    ): RedirectResponse {
        $this->ensureTemplateBelongsToOrganization(
            $consentTemplate
        );

        DB::transaction(
            function () use ($consentTemplate): void {
                $lockedTemplate = ConsentTemplate::query()
                    ->whereKey($consentTemplate->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureTemplateBelongsToOrganization(
                    $lockedTemplate
                );

                if ($lockedTemplate->status === 'archived') {
                    throw ValidationException::withMessages([
                        'template' =>
                            'This consent template is already archived.',
                    ]);
                }

                if ($lockedTemplate->active_version_id !== null) {
                    throw ValidationException::withMessages([
                        'template' =>
                            'Unpublish this consent template before archiving it.',
                    ]);
                }

                $lockedTemplate->update([
                    'active_version_id' => null,
                    'status' => 'archived',
                ]);
            },
            3
        );

        return redirect()
            ->route('consent-templates.manage')
            ->with(
                'success',
                'Consent template archived successfully.'
            );
    }

    /**
     * Validate template creation and editing fields.
     */
    private function validateTemplateRequest(
        Request $request
    ): array {
        return $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'usage_types' => [
                'required',
                'array',
                'min:1',
            ],

            'usage_types.*' => [
                'required',
                'string',
                'distinct',

                Rule::in([
                    ConsentTemplate::USAGE_INDIVIDUAL,
                    ConsentTemplate::USAGE_SIGNING_STATION,
                ]),
            ],

            'content' => [
                'required',
                'string',
            ],

            'additional_fields_json' => [
                'required',
                'string',
            ],
        ], [
            'usage_types.required' =>
                'Select at least one template workflow.',

            'usage_types.array' =>
                'Select a valid template workflow.',

            'usage_types.min' =>
                'Select Individual consent, Public signing station, or both.',
        ]);
    }

    /**
     * Convert the workflow checkboxes into the stored usage value.
     */
    private function templateUsageType(
        array $usageTypes
    ): string {
        $hasIndividual = in_array(
            ConsentTemplate::USAGE_INDIVIDUAL,
            $usageTypes,
            true
        );

        $hasSigningStation = in_array(
            ConsentTemplate::USAGE_SIGNING_STATION,
            $usageTypes,
            true
        );

        if ($hasIndividual && $hasSigningStation) {
            return ConsentTemplate::USAGE_BOTH;
        }

        return $hasIndividual
            ? ConsentTemplate::USAGE_INDIVIDUAL
            : ConsentTemplate::USAGE_SIGNING_STATION;
    }

    /**
     * Check whether a stored usage value includes signing stations.
     */
    private function usageSupportsSigningStation(
        string $usageType
    ): bool {
        return in_array(
            $usageType,
            [
                ConsentTemplate::USAGE_SIGNING_STATION,
                ConsentTemplate::USAGE_BOTH,
            ],
            true
        );
    }

    /**
     * Decode, validate, and clean dynamic fields.
     */
    private function prepareAdditionalFields(
        string $fieldsJson
    ): array {
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

        $cleanAdditionalFields = [];

        foreach ($additionalFields as $field) {
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

            $cleanAdditionalFields[] = [
                'id' => $fieldId,
                'label' => $label,
                'required' =>
                    (bool) ($field['required'] ?? false),
            ];
        }

        return $cleanAdditionalFields;
    }

    /**
     * Prevent one organization from accessing another
     * organization's consent templates.
     */
    private function ensureTemplateBelongsToOrganization(
        ConsentTemplate $consentTemplate
    ): void {
        abort_unless(
            (int) $consentTemplate->organization_id ===
                (int) Auth::user()->organization_id,
            403
        );
    }
}
