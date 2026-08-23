<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AccountRestorationRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\PermissionRegistrar;

class AccountRestorationRequestController extends Controller
{
    /**
     * Send a restoration request after the user has already proved
     * ownership of the affected account during authentication.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $context =
            $request->session()->get(
                'account_restoration'
            );

        abort_unless(
            is_array($context),
            404
        );

        $expiresAt =
            (int) (
                $context['expires_at']
                ?? 0
            );

        if ($expiresAt < now()->timestamp) {
            $request->session()->forget(
                'account_restoration'
            );

            return redirect()
                ->route('login')
                ->with(
                    'status',
                    'Your restoration verification has expired. '
                    .'Sign in again to request restoration.'
                );
        }

        $type =
            $context['type']
            ?? null;

        $userId =
            (int) (
                $context['user_id']
                ?? 0
            );

        $requester =
            User::withTrashed()
                ->with('organization')
                ->find($userId);

        abort_unless(
            $requester !== null
            && $requester->organization_id !== null
            && $requester->organization !== null,
            404
        );

        /*
         * Rate-limit by the verified account rather than by IP address.
         * Shared organization networks therefore do not block each other.
         */
        $rateLimitKey =
            'account-restoration:'
            .$type
            .':'
            .$requester->id;

        if (
            RateLimiter::tooManyAttempts(
                $rateLimitKey,
                1
            )
        ) {
            $minutes =
                max(
                    1,
                    (int) ceil(
                        RateLimiter::availableIn(
                            $rateLimitKey
                        ) / 60
                    )
                );

            return back()->with(
                'status',
                'A restoration request was already sent recently. '
                ."Please wait about {$minutes} minute"
                .($minutes === 1 ? '' : 's')
                .' before requesting again.'
            );
        }

        if ($type === 'user') {
            return $this->requestUserRestoration(
                $request,
                $requester,
                $rateLimitKey
            );
        }

        if ($type === 'organization') {
            return $this->requestOrganizationRestoration(
                $request,
                $requester,
                $rateLimitKey
            );
        }

        abort(404);
    }

    private function requestUserRestoration(
        Request $request,
        User $requester,
        string $rateLimitKey
    ): RedirectResponse {
        $organization =
            $requester->organization;

        /*
         * If the whole organization became archived after the login
         * attempt, escalate this as an organization restoration request.
         */
        if ($organization->isArchived()) {
            return $this->requestOrganizationRestoration(
                $request,
                $requester,
                $rateLimitKey
            );
        }

        abort_unless(
            $requester->trashed(),
            404
        );

        /*
         * This is a guest route, so organization role middleware has not
         * established Spatie's team context for this request.
         */
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(
                $organization->id
            );

        $administrators =
            User::query()
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereNotNull(
                    'email_verified_at'
                )
                ->whereHas(
                    'roles',
                    function ($query) use (
                        $organization
                    ): void {
                        $query
                            ->where(
                                'roles.organization_id',
                                $organization->id
                            )
                            ->where(
                                'roles.guard_name',
                                'web'
                            )
                            ->where(
                                'roles.name',
                                OrganizationRole::
                                    ORGANIZATION_ADMINISTRATOR
                                    ->label()
                            );
                    }
                )
                ->get();

        if ($administrators->isNotEmpty()) {
            Notification::send(
                $administrators,
                new AccountRestorationRequested(
                    requestType:
                        'user',
                    requesterName:
                        $requester->name,
                    requesterEmail:
                        $requester->email,
                    organizationName:
                        $organization->name,
                    reviewUrl:
                        route(
                            'organization-users.archived',
                            $organization
                        ),

                    requestedAt:
                        $this->requestedAt()
                )
            );
        }

        RateLimiter::hit(
            $rateLimitKey,
            3600
        );

        $request->session()->forget(
            'account_restoration'
        );

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Your restoration request has been sent to your '
                .'Organization Administrator.'
            );
    }

    /**
     * Format the request time in the application's display timezone.
     */
    private function requestedAt(): string
    {
        $timezone =
            config('app.display_timezone')
            ?: config(
                'app.timezone',
                'UTC'
            );

        return now()
            ->timezone($timezone)
            ->format(
                'M d, Y H:i T'
            );
    }

    private function requestOrganizationRestoration(
        Request $request,
        User $requester,
        string $rateLimitKey
    ): RedirectResponse {
        /** @var Organization $organization */
        $organization =
            $requester->organization;

        abort_unless(
            $organization->isArchived()
            && (
                $requester->trashed()
                || $requester->is_active
            ),
            404
        );

        $superAdmins =
            User::query()
                ->whereNull(
                    'organization_id'
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereNotNull(
                    'email_verified_at'
                )
                ->whereHas(
                    'platformRole',
                    fn ($query) =>
                        $query->where(
                            'slug',
                            'super-admin'
                        )
                )
                ->get();

        if ($superAdmins->isNotEmpty()) {
            Notification::send(
                $superAdmins,
                new AccountRestorationRequested(
                    requestType:
                        'organization',
                    requesterName:
                        $requester->name,
                    requesterEmail:
                        $requester->email,
                    organizationName:
                        $organization->name,
                    reviewUrl:
                        route(
                            'platform.organizations.show',
                            $organization
                        ),

                    requestedAt:
                        $this->requestedAt()
                )
            );
        }

        RateLimiter::hit(
            $rateLimitKey,
            3600
        );

        $request->session()->forget(
            'account_restoration'
        );

        return redirect()
            ->route('login')
            ->with(
                'status',
                'Your organization restoration request has been sent '
                .'to the Platform Super Admin.'
            );
    }
}
