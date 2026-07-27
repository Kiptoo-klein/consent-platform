<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationUser
{
    /**
     * Allow only authenticated users attached to a valid organization.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        abort_unless(
            $user !== null,
            401,
            'You must be signed in to access this page.'
        );

        abort_if(
            empty($user->organization_id),
            403,
            'This page is only available to organization users.'
        );

        $user->loadMissing('organization');

        abort_if(
            $user->organization === null,
            403,
            'Your account is not attached to a valid organization.'
        );

        return $next($request);
    }
}
