<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationSubscriptionAccess
{
    /**
     * Block organization workflows until payment or Platform Admin approval.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response|RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user !== null,
            401,
            'You must be signed in to access this page.'
        );

        $user->loadMissing('organization.subscription');

        $subscription = $user->organization?->subscription;

        if (
            $subscription === null
            || ! $subscription->allowsOrganizationAccess()
        ) {
            return redirect()->route(
                'organization-subscription.show'
            );
        }

        return $next($request);
    }
}
