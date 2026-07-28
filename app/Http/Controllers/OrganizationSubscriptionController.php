<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrganizationSubscriptionController extends Controller
{
    /**
     * Display the organization's subscription and access status.
     */
    public function show(Request $request): View
    {
        $organization = $request->user()
            ->organization()
            ->with('subscription.plan')
            ->firstOrFail();

        return view('organization-subscription.show', [
            'organization' => $organization,
            'subscription' => $organization->subscription,
        ]);
    }
}
