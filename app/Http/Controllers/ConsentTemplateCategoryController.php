<?php

namespace App\Http\Controllers;

use App\Models\ConsentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ConsentTemplateCategoryController extends Controller
{
    /**
     * Assign, change, or remove a category from an organization's template.
     */
    public function update(
        Request $request
    ): RedirectResponse {
        $organizationId = (int) $request->user()->organization_id;

        $validated = $request->validate([
            'template_id' => [
                'required',
                'integer',
                Rule::exists('consent_templates', 'id')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'organization_id',
                                $organizationId
                            )
                    ),
            ],

            'category' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $consentTemplate = ConsentTemplate::query()
            ->where('organization_id', $organizationId)
            ->findOrFail((int) $validated['template_id']);

        $category = isset($validated['category'])
            ? Str::of($validated['category'])
                ->squish()
                ->toString()
            : null;

        if ($category === '') {
            $category = null;
        }

        $consentTemplate->update([
            'category' => $category,
        ]);

        return redirect()
            ->route(
                'consent-sessions.index',
                [
                    'template_id' =>
                        $consentTemplate->id,
                ]
            )
            ->with(
                'success',
                $category === null
                    ? "The category was removed from {$consentTemplate->title}."
                    : "{$consentTemplate->title} was assigned to the {$category} category."
            );
    }
}
