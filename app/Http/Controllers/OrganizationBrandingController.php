<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class OrganizationBrandingController extends Controller
{
    /**
     * Display the organization branding settings page.
     */
    public function edit(Request $request): View
    {
        $organization = $this->organizationFor($request);

        return view('organization-branding.edit', [
            'organization' => $organization,
        ]);
    }

    /**
     * Update the organization's branding settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $organization = $this->organizationFor($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'support_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'primary_color' => [
                'required',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'secondary_color' => [
                'required',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'accent_color' => [
                'required',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'pdf_primary_color' => [
                'required',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'pdf_accent_color' => [
                'required',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'footer_text' => [
                'nullable',
                'string',
                'max:500',
            ],

            'logo' => [
                'nullable',
                'image',
                Rule::file()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max(2 * 1024),
            ],

            'remove_logo' => [
                'nullable',
                'boolean',
            ],
        ], [
            'primary_color.regex' => 'The primary color must be a valid hexadecimal color, such as #4F46E5.',
            'secondary_color.regex' => 'The secondary color must be a valid hexadecimal color, such as #6366F1.',
            'accent_color.regex' => 'The accent color must be a valid hexadecimal color, such as #10B981.',
            'pdf_primary_color.regex' => 'The PDF primary color must be a valid hexadecimal color, such as #17324D.',
            'pdf_accent_color.regex' => 'The PDF accent color must be a valid hexadecimal color, such as #0F766E.',
            'logo.max' => 'The logo must not be larger than 2 MB.',
        ]);

        foreach ([
            'primary_color',
            'secondary_color',
            'accent_color',
            'pdf_primary_color',
            'pdf_accent_color',
        ] as $colorField) {
            $validated[$colorField] = strtoupper(
                $validated[$colorField]
            );
        }

        $brandingDisk =
            (string) config(
                'organization-branding.disk',
                'public'
            );

        $oldLogoPath = $organization->logo;
        $newLogoPath = null;

        try {
            if ($request->hasFile('logo')) {
                $newLogoPath = $request
                    ->file('logo')
                    ->store(
                        'organization-logos',
                        $brandingDisk
                    );

                $validated['logo'] = $newLogoPath;
            }

            if ($request->boolean('remove_logo') && ! $newLogoPath) {
                $validated['logo'] = null;
            }

            unset($validated['remove_logo']);

            $organization->update($validated);

            $logoWasReplaced = $newLogoPath !== null;
            $logoWasRemoved = $request->boolean('remove_logo')
                && $newLogoPath === null;

            if (
                $oldLogoPath
                && ($logoWasReplaced || $logoWasRemoved)
                && Storage::disk($brandingDisk)->exists($oldLogoPath)
            ) {
                Storage::disk($brandingDisk)->delete($oldLogoPath);
            }
        } catch (Throwable $exception) {
            if (
                $newLogoPath
                && Storage::disk($brandingDisk)->exists($newLogoPath)
            ) {
                Storage::disk($brandingDisk)->delete($newLogoPath);
            }

            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'branding' => 'The branding settings could not be saved. Please try again.',
                ]);
        }

        return redirect()
            ->route('organization-branding.edit')
            ->with('status', 'Organization branding updated successfully.');
    }

    /**
     * Get the authenticated user's organization.
     */
    private function organizationFor(Request $request): Organization
    {
        $organizationId = $request->user()?->organization_id;

        abort_unless(
            $organizationId,
            403,
            'Your account is not assigned to an organization.'
        );

        return Organization::query()->findOrFail($organizationId);
    }
}

