<?php

namespace Tests\Unit;

use App\Services\ContextualHelpService;
use Tests\TestCase;

class ContextualHelpServiceTest extends TestCase
{
    public function test_every_supported_application_page_has_specific_detailed_help(): void
    {
        $expectedContexts = [
            'dashboard' =>
                'dashboard',

            'organization-settings.index' =>
                'settings',

            'organization-users.index' =>
                'users',

            'organization-users.create' =>
                'users-create',

            'organization-users.edit' =>
                'users-edit',

            'organization-users.archived' =>
                'users-archived',

            'organization-branding.edit' =>
                'branding',

            'organization-subscription.show' =>
                'subscription',

            'organization-subscription-plans.index' =>
                'subscription-plans',

            'organization-billing.index' =>
                'billing-management',

            'organization-billing.invoices.show' =>
                'billing-invoice',

            'organization-billing.receipts.show' =>
                'billing-receipt',

            'organization-billing.reminder-preferences.index' =>
                'billing-reminder-preferences',

            'organization-billing.reminder-recipients.index' =>
                'billing-reminder-recipients',

            'consent-templates.index' =>
                'templates',

            'consent-templates.archived' =>
                'templates-archived',

            'consent-templates.new' =>
                'templates-new',

            'consent-templates.create' =>
                'templates-create',

            'consent-templates.individual.create' =>
                'templates-individual-create',

            'consent-templates.manage' =>
                'templates-manage',

            'consent-templates.edit' =>
                'templates-edit',

            'consent-templates.preview' =>
                'templates-preview',

            'consent-templates.published' =>
                'templates-published',

            'consent-templates.history' =>
                'templates-history',

            'consent-sessions.index' =>
                'consent-records',

            'consent-sessions.select-template' =>
                'consent-select-template',

            'consent-sessions.create' =>
                'consent-create',

            'consent-sessions.show' =>
                'consent-record',

            'consent-sessions.export-data' =>
                'consent-export-data',

            'consent-sessions.audit' =>
                'consent-audit',

            'consent-campaigns.index' =>
                'campaigns',

            'consent-campaigns.select-template' =>
                'campaign-select-template',

            'consent-campaigns.create' =>
                'campaign-create',

            'consent-campaigns.show' =>
                'campaign-show',

            'signing-stations.index' =>
                'signing-stations',

            'signing-stations.create' =>
                'signing-stations-create',

            'signing-stations.show' =>
                'signing-stations-show',

            'signing-stations.edit' =>
                'signing-stations-edit',

            'signing-stations.analytics' =>
                'signing-stations-analytics',

            'profile.edit' =>
                'profile',
        ];

        $service =
            app(ContextualHelpService::class);

        foreach (
            $expectedContexts
            as $routeName => $expectedContext
        ) {
            $help =
                $service->resolve(
                    $routeName
                );

            $this->assertSame(
                $expectedContext,
                $help['context'],
                "Wrong help context for {$routeName}."
            );

            $this->assertNotSame(
                'general',
                $help['context'],
                "{$routeName} fell back to general help."
            );

            $this->assertNotEmpty(
                $help['title'],
                "{$routeName} has no help title."
            );

            $this->assertNotEmpty(
                $help['intro'],
                "{$routeName} has no help introduction."
            );

            $this->assertNotEmpty(
                $help['sections'],
                "{$routeName} has no detailed help sections."
            );

            foreach (
                $help['sections']
                as $section
            ) {
                $this->assertContains(
                    $section['type'],
                    [
                        'guide',
                        'important',
                        'warning',
                    ],
                    "{$routeName} contains an invalid help section type."
                );

                $this->assertNotEmpty(
                    $section['title'],
                    "{$routeName} contains an untitled help section."
                );

                $this->assertGreaterThanOrEqual(
                    2,
                    count($section['tips']),
                    "{$routeName} contains an under-detailed help section."
                );

                foreach (
                    $section['tips']
                    as $tip
                ) {
                    $this->assertNotEmpty(
                        trim($tip),
                        "{$routeName} contains an empty help tip."
                    );
                }
            }
        }
    }

    public function test_high_impact_pages_have_visible_warning_guidance(): void
    {
        $routesExpectedToWarn = [
            'organization-users.index',
            'organization-users.create',
            'organization-users.edit',
            'organization-users.archived',

            'organization-subscription.show',
            'organization-subscription-plans.index',

            'organization-billing.index',
            'organization-billing.invoices.show',
            'organization-billing.reminder-recipients.index',

            'consent-templates.create',
            'consent-templates.individual.create',
            'consent-templates.manage',
            'consent-templates.edit',
            'consent-templates.preview',
            'consent-templates.published',

            'consent-sessions.show',
            'consent-sessions.export-data',

            'consent-campaigns.select-template',
            'consent-campaigns.create',

            'signing-stations.create',
            'signing-stations.show',
            'signing-stations.edit',

            'profile.edit',
        ];

        $service =
            app(ContextualHelpService::class);

        foreach (
            $routesExpectedToWarn
            as $routeName
        ) {
            $help =
                $service->resolve(
                    $routeName
                );

            $types =
                array_column(
                    $help['sections'],
                    'type'
                );

            $this->assertContains(
                'warning',
                $types,
                "{$routeName} should contain a warning section."
            );
        }
    }

    public function test_unknown_child_routes_use_module_help_instead_of_general_help(): void
    {
        $fallbacks = [
            'organization-users.future-page' =>
                'users',

            'organization-billing.future-page' =>
                'billing-management',

            'organization-subscription.future-page' =>
                'subscription',

            'organization-branding.future-page' =>
                'branding',

            'organization-settings.future-page' =>
                'settings',

            'consent-templates.future-page' =>
                'templates',

            'consent-sessions.future-page' =>
                'consent-records',

            'consent-campaigns.future-page' =>
                'campaigns',

            'signing-stations.future-page' =>
                'signing-stations',
        ];

        $service =
            app(ContextualHelpService::class);

        foreach (
            $fallbacks
            as $routeName => $expectedContext
        ) {
            $this->assertSame(
                $expectedContext,
                $service
                    ->resolve($routeName)[
                        'context'
                    ]
            );
        }
    }

    public function test_truly_unknown_route_uses_safe_general_help(): void
    {
        $help =
            app(ContextualHelpService::class)
                ->resolve(
                    'some-new-area.index'
                );

        $this->assertSame(
            'general',
            $help['context']
        );

        $this->assertNotEmpty(
            $help['sections']
        );
    }
}
