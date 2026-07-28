<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Handles platform-level organization administration.
 *
 * These actions belong to the central platform administration area rather
 * than an individual organization's dashboard. Access is protected by the
 * platform role middleware configured in the platform route group.
 */
class OrganizationController extends Controller
{
    /**
     * Display a paginated list of all organizations.
     */
    public function index(): View
    {
        $organizations = Organization::query()
            ->withCount([
                'users',
                'consentTemplates',
            ])
            ->latest()
            ->paginate(15);

        return view('platform.organizations.index', compact('organizations'));
    }

    /**
     * Display a single organization and its summary statistics.
     */
    public function show(Organization $organization): View
    {
        $organization
            ->load([
                'subscription.plan',
                'subscription.billingOwner',
                'subscription.bypassApprover',
            ])
            ->loadCount([
                'users',
                'consentTemplates',
            ]);

        return view('platform.organizations.show', compact('organization'));
    }

    /**
     * Display the organization edit form.
     */
    public function edit(Organization $organization): View
    {
        return view('platform.organizations.edit', compact('organization'));
    }

    /**
     * Update an organization's profile, contact information, and branding.
     */
    public function update(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('organizations', 'slug')
                    ->ignore($organization->id),
            ],

            'email' => [
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

            'support_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'footer_text' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'primary_color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'secondary_color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],

            'accent_color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        $organization->update($validated);

        return redirect()
            ->route('platform.organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }

    /**
     * Allow an organization to operate without confirmed payment.
     */
    public function approveSubscriptionBypass(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $subscription = $organization
            ->subscription()
            ->firstOrFail();

        $subscription->update([
            'bypass_approved_at' => now(),
            'bypass_approved_by_user_id' =>
                $request->user()->id,
            'bypass_reason' => trim($validated['reason']),
        ]);

        return redirect()
            ->route(
                'platform.organizations.show',
                $organization
            )
            ->with(
                'success',
                'Organization subscription bypass approved.'
            );
    }

    /**
     * Remove an organization's payment bypass.
     */
    public function revokeSubscriptionBypass(
        Organization $organization
    ): RedirectResponse {
        $subscription = $organization
            ->subscription()
            ->firstOrFail();

        $subscription->update([
            'bypass_approved_at' => null,
            'bypass_approved_by_user_id' => null,
            'bypass_reason' => null,
        ]);

        return redirect()
            ->route(
                'platform.organizations.show',
                $organization
            )
            ->with(
                'success',
                'Organization subscription bypass revoked.'
            );
    }

    /**
     * Display users belonging to a specific organization.
     *
     * The relationship is paginated so the page remains efficient when an
     * organization eventually contains hundreds or thousands of users.
     */
    public function users(Organization $organization): View
    {
        $users = $organization
            ->users()
            ->orderBy('name')
            ->paginate(15);

        return view(
            'platform.organizations.users.index',
            compact('organization', 'users')
        );
    }
}
