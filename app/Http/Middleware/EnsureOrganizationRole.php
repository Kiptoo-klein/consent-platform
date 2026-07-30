<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationRole
{
    /**
     * Restrict a route to a specific organization role.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $role
    ): Response {
        $user = $request->user();

        abort_unless(
            $user !== null
            && $user->organization_id !== null,
            403,
            'This page is only available to organization users.'
        );

        $organizationRole = OrganizationRole::tryFrom($role);

        abort_unless(
            $organizationRole !== null,
            403,
            'The required organization role is invalid.'
        );

        $routeOrganization =
            $request->route('organization');

        if ($routeOrganization instanceof Organization) {
            abort_unless(
                (int) $routeOrganization->id
                    === (int) $user->organization_id,
                403,
                'You cannot manage another organization.'
            );
        }

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($user->organization_id);

        abort_unless(
            $user->hasRole($organizationRole->label()),
            403,
            'Your organization role does not allow access.'
        );

        return $next($request);
    }
}
