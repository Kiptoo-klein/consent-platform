<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

final class BackNavigation
{
    /**
     * Resolve a predictable parent page for the current application route.
     *
     * Browser referrers are intentionally not used. A page reached after a
     * POST/PUT/PATCH redirect must never send the user back into the completed
     * form or action that produced it.
     */
    public static function resolve(Request $request): string
    {
        $route = $request->route();
        $routeName = (string) ($route?->getName() ?? '');
        $parameters = $route?->parameters() ?? [];

        if (
            ! array_key_exists('organization', $parameters)
            && $request->user()?->organization_id
        ) {
            $parameters['organization'] =
                $request->user()->organization_id;
        }

        return self::forRoute(
            routeName: $routeName,
            parameters: $parameters,
            fallback: self::fallback($request)
        );
    }

    /**
     * Resolve a stable destination from a route name and its parameters.
     *
     * This public pure entry point keeps the navigation rules directly
     * testable without browser history or session state.
     */
    public static function forRoute(
        string $routeName,
        array $parameters,
        string $fallback
    ): string {
        $organization = self::parameter(
            $parameters,
            'organization'
        );

        $consentSession = self::parameter(
            $parameters,
            'consentSession'
        );

        $signingStation = self::parameter(
            $parameters,
            'signingStation'
        );

        return match (true) {
            /* Consent records. */
            $routeName === 'consent-sessions.audit' =>
                self::to(
                    'consent-sessions.show',
                    ['consentSession' => $consentSession],
                    $fallback
                ),

            $routeName === 'consent-sessions.show' =>
                self::to(
                    'consent-sessions.index',
                    [],
                    $fallback
                ),

            $routeName === 'consent-sessions.create' =>
                self::to(
                    'consent-sessions.select-template',
                    [],
                    $fallback
                ),

            $routeName === 'consent-sessions.select-template' =>
                self::to(
                    'consent-sessions.index',
                    [],
                    $fallback
                ),

            $routeName === 'consent-sessions.index' =>
                $fallback,

            str_starts_with($routeName, 'consent-sessions.') =>
                self::to(
                    'consent-sessions.index',
                    [],
                    $fallback
                ),

            /* Consent templates. */
            in_array(
                $routeName,
                [
                    'consent-templates.create',
                    'consent-templates.individual.create',
                ],
                true
            ) =>
                self::to(
                    'consent-templates.new',
                    [],
                    $fallback
                ),

            in_array(
                $routeName,
                [
                    'consent-templates.edit',
                    'consent-templates.preview',
                    'consent-templates.published',
                    'consent-templates.history',
                    'consent-templates.archived',
                    'consent-templates.new',
                ],
                true
            ) =>
                self::to(
                    'consent-templates.manage',
                    [],
                    $fallback
                ),

            in_array(
                $routeName,
                [
                    'consent-templates.index',
                    'consent-templates.manage',
                ],
                true
            ) =>
                $fallback,

            str_starts_with($routeName, 'consent-templates.') =>
                self::to(
                    'consent-templates.manage',
                    [],
                    $fallback
                ),

            /* Signing stations. */
            $routeName === 'signing-stations.edit' =>
                self::to(
                    'signing-stations.show',
                    ['signingStation' => $signingStation],
                    $fallback
                ),

            in_array(
                $routeName,
                [
                    'signing-stations.show',
                    'signing-stations.create',
                    'signing-stations.analytics',
                ],
                true
            ) =>
                self::to(
                    'signing-stations.index',
                    [],
                    $fallback
                ),

            $routeName === 'signing-stations.index' =>
                $fallback,

            str_starts_with($routeName, 'signing-stations.') =>
                self::to(
                    'signing-stations.index',
                    [],
                    $fallback
                ),

            /* Organization administration. */
            $routeName === 'organization-users.index' =>
                $fallback,

            str_starts_with($routeName, 'organization-users.') =>
                self::to(
                    'organization-users.index',
                    ['organization' => $organization],
                    $fallback
                ),

            $routeName ===
                'organization-billing.invoices.show' =>
                self::to(
                    'organization-subscription-plans.index',
                    [],
                    $fallback
                ),

            str_starts_with($routeName, 'organization-billing.')
            && $routeName !== 'organization-billing.index' =>
                self::to(
                    'organization-billing.index',
                    [],
                    $fallback
                ),

            $routeName === 'organization-billing.index' =>
                self::to(
                    'organization-subscription.show',
                    [],
                    $fallback
                ),

            str_starts_with(
                $routeName,
                'organization-subscription-plans.'
            ) =>
                self::to(
                    'organization-subscription.show',
                    [],
                    $fallback
                ),

            str_starts_with($routeName, 'organization-subscription.') =>
                $fallback,

            str_starts_with($routeName, 'organization-branding.') =>
                $fallback,

            /* Platform organization billing hierarchy. */
            $routeName ===
                'platform.organizations.subscription-invoices.show' =>
                self::to(
                    'platform.organizations.subscription-invoices.index',
                    ['organization' => $organization],
                    $fallback
                ),

            $routeName ===
                'platform.organizations.subscription-invoices.index' =>
                self::to(
                    'platform.organizations.show',
                    ['organization' => $organization],
                    $fallback
                ),

            str_starts_with(
                $routeName,
                'platform.organizations.subscription-invoices.'
            ) =>
                self::to(
                    'platform.organizations.subscription-invoices.index',
                    ['organization' => $organization],
                    $fallback
                ),

            $routeName ===
                'platform.organizations.subscription-transactions.show' =>
                self::to(
                    'platform.organizations.subscription-transactions.index',
                    ['organization' => $organization],
                    $fallback
                ),

            $routeName ===
                'platform.organizations.subscription-transactions.index' =>
                self::to(
                    'platform.organizations.show',
                    ['organization' => $organization],
                    $fallback
                ),

            str_starts_with(
                $routeName,
                'platform.organizations.subscription-transactions.'
            ) =>
                self::to(
                    'platform.organizations.subscription-transactions.index',
                    ['organization' => $organization],
                    $fallback
                ),

            /* Platform organization users. */
            $routeName === 'platform.organizations.users.index' =>
                self::to(
                    'platform.organizations.show',
                    ['organization' => $organization],
                    $fallback
                ),

            str_starts_with(
                $routeName,
                'platform.organizations.users.'
            ) =>
                self::to(
                    'platform.organizations.users.index',
                    ['organization' => $organization],
                    $fallback
                ),

            /* Platform organization pages. */
            $routeName === 'platform.organizations.edit' =>
                self::to(
                    'platform.organizations.show',
                    ['organization' => $organization],
                    $fallback
                ),

            $routeName === 'platform.organizations.show' =>
                self::to(
                    'platform.organizations.index',
                    [],
                    $fallback
                ),

            $routeName === 'platform.organizations.index' =>
                $fallback,

            str_starts_with($routeName, 'platform.organizations.') =>
                self::to(
                    'platform.organizations.show',
                    ['organization' => $organization],
                    $fallback
                ),

            /* Platform logs and staff. */
            $routeName === 'platform.activity-logs.show' =>
                self::to(
                    'platform.activity-logs.index',
                    [],
                    $fallback
                ),

            $routeName === 'platform.activity-logs.index' =>
                $fallback,

            str_starts_with($routeName, 'platform.activity-logs.') =>
                self::to(
                    'platform.activity-logs.index',
                    [],
                    $fallback
                ),

            $routeName === 'platform.staff.index' =>
                $fallback,

            str_starts_with($routeName, 'platform.staff.') =>
                self::to(
                    'platform.staff.index',
                    [],
                    $fallback
                ),

            /* Platform billing configuration. */
            str_starts_with($routeName, 'platform.subscription-plans.'),
            str_starts_with(
                $routeName,
                'platform.subscription-invoice-reminder-settings.'
            ),
            str_starts_with(
                $routeName,
                'platform.subscription-payment-settings.'
            ) =>
                self::to(
                    'platform.billing.index',
                    [],
                    $fallback
                ),

            $routeName === 'platform.billing.index' =>
                $fallback,

            /* Remaining platform tools and account pages. */
            str_starts_with($routeName, 'platform.'),
            str_starts_with($routeName, 'profile.') =>
                $fallback,

            default =>
                $fallback,
        };
    }

    private static function fallback(Request $request): string
    {
        $routeName = (string) (
            $request->route()?->getName()
            ?? ''
        );

        if (
            str_starts_with($routeName, 'platform.')
            && Route::has('platform.dashboard')
        ) {
            return route('platform.dashboard');
        }

        if (Route::has('dashboard')) {
            return route('dashboard');
        }

        return url('/');
    }

    private static function parameter(
        array $parameters,
        string $name
    ): mixed {
        return $parameters[$name] ?? null;
    }

    private static function to(
        string $routeName,
        array $parameters,
        string $fallback
    ): string {
        if (! Route::has($routeName)) {
            return $fallback;
        }

        $route = Route::getRoutes()->getByName(
            $routeName
        );

        if ($route === null) {
            return $fallback;
        }

        foreach ($route->parameterNames() as $name) {
            if (
                ! array_key_exists($name, $parameters)
                || $parameters[$name] === null
                || $parameters[$name] === ''
            ) {
                return $fallback;
            }
        }

        return route($routeName, $parameters);
    }
}
