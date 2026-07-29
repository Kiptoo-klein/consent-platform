<?php

namespace App\Http\Controllers;

use App\Models\SigningStation;
use App\Models\User;
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

        $usage = [
            /*
             * Disabled users still occupy subscription seats. Archived
             * users are excluded by the User model's SoftDeletes scope.
             */
            'users' => $organization->users()->count(),

            'consent_managers' => $this->roleUsage(
                $organization->id,
                'Consent Manager'
            ),

            'staff' => $this->roleUsage(
                $organization->id,
                'Staff'
            ),

            'auditors' => $this->roleUsage(
                $organization->id,
                'Auditor'
            ),

            'active_kiosks' => SigningStation::query()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->where('active', true)
                ->count(),
        ];

        return view('organization-subscription.show', [
            'organization' => $organization,
            'subscription' => $organization->subscription,
            'usage' => $usage,
        ]);
    }

    /**
     * Count non-archived organization users assigned to a role.
     */
    private function roleUsage(
        int $organizationId,
        string $roleName
    ): int {
        return User::query()
            ->where(
                'users.organization_id',
                $organizationId
            )
            ->whereHas(
                'roles',
                function ($query) use (
                    $organizationId,
                    $roleName
                ): void {
                    $query
                        ->where(
                            'roles.organization_id',
                            $organizationId
                        )
                        ->where('roles.guard_name', 'web')
                        ->where('roles.name', $roleName);
                }
            )
            ->count();
    }
}
