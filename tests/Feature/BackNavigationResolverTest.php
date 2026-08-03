<?php

namespace Tests\Feature;

use App\Support\BackNavigation;
use Tests\TestCase;

class BackNavigationResolverTest extends TestCase
{
    public function test_workflow_destinations_use_stable_parent_routes(): void
    {
        $fallback = route('dashboard');

        $this->assertSame(
            route('consent-sessions.index'),
            BackNavigation::forRoute(
                'consent-sessions.show',
                ['consentSession' => 91],
                $fallback
            )
        );

        $this->assertSame(
            route(
                'consent-sessions.show',
                ['consentSession' => 91]
            ),
            BackNavigation::forRoute(
                'consent-sessions.audit',
                ['consentSession' => 91],
                $fallback
            )
        );

        $this->assertSame(
            route('consent-templates.manage'),
            BackNavigation::forRoute(
                'consent-templates.preview',
                ['consentTemplate' => 22],
                $fallback
            )
        );

        $this->assertSame(
            route('consent-templates.new'),
            BackNavigation::forRoute(
                'consent-templates.individual.create',
                [],
                $fallback
            )
        );

        $this->assertSame(
            route(
                'signing-stations.show',
                ['signingStation' => 17]
            ),
            BackNavigation::forRoute(
                'signing-stations.edit',
                ['signingStation' => 17],
                $fallback
            )
        );
    }

    public function test_organization_invoice_returns_to_subscription_plans(): void
    {
        $fallback =
            route(
                'dashboard'
            );

        $this->assertSame(
            route(
                'organization-subscription-plans.index'
            ),
            BackNavigation::forRoute(
                'organization-billing.invoices.show',
                [
                    'subscriptionInvoice' =>
                        12,
                ],
                $fallback
            )
        );

        $this->assertSame(
            route(
                'organization-subscription.show'
            ),
            BackNavigation::forRoute(
                'organization-billing.index',
                [],
                $fallback
            )
        );
    }

    public function test_platform_nested_pages_return_through_their_hierarchy(): void
    {
        $fallback = route('platform.dashboard');

        $this->assertSame(
            route(
                'platform.organizations.subscription-invoices.index',
                ['organization' => 8]
            ),
            BackNavigation::forRoute(
                'platform.organizations.subscription-invoices.show',
                [
                    'organization' => 8,
                    'subscriptionInvoice' => 12,
                ],
                $fallback
            )
        );

        $this->assertSame(
            route(
                'platform.organizations.subscription-transactions.index',
                ['organization' => 8]
            ),
            BackNavigation::forRoute(
                'platform.organizations.subscription-transactions.show',
                [
                    'organization' => 8,
                    'subscriptionTransaction' => 13,
                ],
                $fallback
            )
        );

        $this->assertSame(
            route(
                'platform.organizations.users.index',
                ['organization' => 8]
            ),
            BackNavigation::forRoute(
                'platform.organizations.users.edit',
                [
                    'organization' => 8,
                    'user' => 14,
                ],
                $fallback
            )
        );

        $this->assertSame(
            route('platform.billing.index'),
            BackNavigation::forRoute(
                'platform.subscription-payment-settings.index',
                [],
                $fallback
            )
        );

        $this->assertSame(
            route('platform.activity-logs.index'),
            BackNavigation::forRoute(
                'platform.activity-logs.show',
                ['activityLog' => 15],
                $fallback
            )
        );
    }

    public function test_unknown_pages_fall_back_to_the_correct_dashboard(): void
    {
        $this->assertSame(
            route('dashboard'),
            BackNavigation::forRoute(
                'unknown.organization-page',
                [],
                route('dashboard')
            )
        );

        $this->assertSame(
            route('platform.dashboard'),
            BackNavigation::forRoute(
                'platform.unknown-page',
                [],
                route('platform.dashboard')
            )
        );
    }
}
