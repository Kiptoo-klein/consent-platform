<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class ContextualHelpService
{
    /**
     * Resolve contextual help for the current named route.
     *
     * @return array{
     *     context: string,
     *     title: string,
     *     intro: string,
     *     sections: array<int, array{
     *         title: string,
     *         type: string,
     *         tips: array<int, string>
     *     }>,
     *     links: array<int, array{
     *         label: string,
     *         route: string
     *     }>
     * }
     */
    public function resolve(
        ?string $routeName,
        ?User $user = null
    ): array {
        $routeName ??= '';

        $routeMap = [
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

        $context =
            $routeMap[$routeName]
            ?? $this->fallbackContext($routeName);

        $definitions =
            $this->definitions();

        $help =
            $definitions[$context]
            ?? $definitions['general'];

        if (
            $context === 'dashboard'
            && $user?->organization
                ?->subscription
                ?->isEvaluation()
        ) {
            array_unshift(
                $help['sections'][0]['tips'],
                'Use the Free Evaluation Getting Started checklist to work through the core eConsent workflow and understand the product before selecting a paid plan.'
            );
        }

        $help['links'] =
            $this->filterLinksForUser(
                $help['links'] ?? [],
                $routeName,
                $user
            );

        return $help;
    }

    /**
     * Keep contextual-help navigation aligned with the application's
     * existing organization access rules.
     *
     * Explanatory help may describe responsibilities the current user
     * does not hold, but the Help drawer must not advertise restricted
     * management destinations as clickable links.
     *
     * @param array<int, array{
     *     label: string,
     *     route: string
     * }> $links
     *
     * @return array<int, array{
     *     label: string,
     *     route: string
     * }>
     */
    private function filterLinksForUser(
        array $links,
        string $sourceRouteName,
        ?User $user
    ): array {
        /*
         * Unit-level help-definition resolution may intentionally run
         * without an authenticated user. Permission filtering belongs
         * to the authenticated rendering path.
         */
        if (
            $user === null
            || $user->organization_id === null
        ) {
            return $links;
        }

        $user->loadMissing(
            'organization.subscription'
        );

        $organization =
            $user->organization;

        if ($organization === null) {
            return [];
        }

        $subscription =
            $organization->subscription;

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(
                $organization->id
            );

        $isOrganizationAdministrator =
            $user->hasRole(
                OrganizationRole::
                    ORGANIZATION_ADMINISTRATOR
                    ->label()
            );

        $isBillingOwner =
            $subscription !== null
            && (int) $subscription
                ->billing_owner_user_id
                === (int) $user->id;

        $hasOrganizationAccess =
            $subscription
                ?->allowsOrganizationAccess()
                ?? false;

        return array_values(
            array_filter(
                $links,
                function (
                    array $link
                ) use (
                    $sourceRouteName,
                    $isOrganizationAdministrator,
                    $isBillingOwner,
                    $hasOrganizationAccess
                ): bool {
                    $targetRoute =
                        $link['route'] ?? '';

                    if (
                        str_starts_with(
                            $targetRoute,
                            'organization-users.'
                        )
                    ) {
                        return
                            $isOrganizationAdministrator;
                    }

                    if (
                        str_starts_with(
                            $targetRoute,
                            'organization-branding.'
                        )
                    ) {
                        return
                            $isOrganizationAdministrator
                            && $hasOrganizationAccess;
                    }

                    if (
                        str_starts_with(
                            $targetRoute,
                            'organization-subscription-plans.'
                        )
                    ) {
                        return
                            $isOrganizationAdministrator
                            || $isBillingOwner;
                    }

                    if (
                        str_starts_with(
                            $targetRoute,
                            'organization-billing.'
                        )
                    ) {
                        /*
                         * Subscription Status intentionally exposes its
                         * billing shortcut only to the assigned Billing
                         * Owner. Match that page exactly.
                         */
                        if (
                            $sourceRouteName
                            === 'organization-subscription.show'
                        ) {
                            return $isBillingOwner;
                        }

                        /*
                         * Other application areas follow the billing
                         * controller rule: Billing Owner or Organization
                         * Admin.
                         */
                        return
                            $isBillingOwner
                            || $isOrganizationAdministrator;
                    }

                    return true;
                }
            )
        );
    }

    private function fallbackContext(
        string $routeName
    ): string {
        if (
            str_starts_with(
                $routeName,
                'organization-users.'
            )
        ) {
            return 'users';
        }

        if (
            str_starts_with(
                $routeName,
                'organization-billing.'
            )
        ) {
            return 'billing-management';
        }

        if (
            str_starts_with(
                $routeName,
                'organization-subscription'
            )
        ) {
            return 'subscription';
        }

        if (
            str_starts_with(
                $routeName,
                'organization-branding.'
            )
        ) {
            return 'branding';
        }

        if (
            str_starts_with(
                $routeName,
                'organization-settings.'
            )
        ) {
            return 'settings';
        }

        if (
            str_starts_with(
                $routeName,
                'consent-templates.'
            )
        ) {
            return 'templates';
        }

        if (
            str_starts_with(
                $routeName,
                'consent-sessions.'
            )
        ) {
            return 'consent-records';
        }

        if (
            str_starts_with(
                $routeName,
                'consent-campaigns.'
            )
        ) {
            return 'campaigns';
        }

        if (
            str_starts_with(
                $routeName,
                'signing-stations.'
            )
        ) {
            return 'signing-stations';
        }

        return 'general';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            'general' => [
                'context' => 'general',
                'title' => 'Help & tips',
                'intro' =>
                    'Use this guide to understand the page you are viewing and where to go next.',
                'sections' => [
                    $this->section(
                        'Getting around eConsent',
                        [
                            'Use the main navigation to move between templates, consent records, signing stations and organization settings.',
                            'Return to the Dashboard when you want a summary of organization activity and shortcuts into common workflows.',
                            'Use Settings for organization administration, subscription, billing, users and branding.',
                        ]
                    ),
                    $this->section(
                        'Before making changes',
                        [
                            'Read page-level warnings before changing access, publishing content or modifying organization-wide settings.',
                            'If an action affects historical records, billing, user access or public signing links, review the consequences before continuing.',
                        ],
                        'important'
                    ),
                ],
                'links' =>
                    $this->defaultLinks(),
            ],

            'dashboard' => [
                'context' => 'dashboard',
                'title' => 'Help & tips',
                'intro' =>
                    'The Dashboard gives you an overview of organization activity and shortcuts into the main eConsent workflows.',
                'sections' => [
                    $this->section(
                        'Using your dashboard',
                        [
                            'Review recent records and templates to return quickly to work already in progress.',
                            'Use the workflow shortcuts when you want to start a consent, work with templates or manage signing stations.',
                            'Use the activity and usage information to understand what has happened in the workspace.',
                        ]
                    ),
                    $this->section(
                        'Where to go next',
                        [
                            'Consent Templates is where reusable consent content is created and managed.',
                            'Consent Records contains individual signing activity and evidence.',
                            'Settings contains organization administration, users, billing, subscription and branding.',
                        ]
                    ),
                ],
                'links' =>
                    $this->defaultLinks(),
            ],

            'settings' => [
                'context' => 'settings',
                'title' => 'Settings help',
                'intro' =>
                    'Settings is the starting point for organization administration. Available options depend on your role and billing responsibilities.',
                'sections' => [
                    $this->section(
                        'What you can manage',
                        [
                            'User Management controls invitations, roles, account status and archived organization users.',
                            'Organization Branding contains organization identity and contact settings.',
                            'Subscription pages show plan access, usage and available plan-management actions.',
                            'Billing pages contain billing ownership, invoices, receipts and reminder controls.',
                        ]
                    ),
                    $this->section(
                        'Access differences',
                        [
                            'Not every organization user can change every setting.',
                            'Organization Admin permissions and Billing Owner responsibilities are separate assignments.',
                            'Subscription state can affect which organization workflows are currently available.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Subscription Status',
                        'route' =>
                            'organization-subscription.show',
                    ],
                    [
                        'label' => 'Billing & Receipts',
                        'route' =>
                            'organization-billing.index',
                    ],
                    [
                        'label' => 'Organization Branding',
                        'route' =>
                            'organization-branding.edit',
                    ],
                ],
            ],

            'users' => [
                'context' => 'users',
                'title' => 'User management help',
                'intro' =>
                    'User Management controls who belongs to the organization, what access they receive and whether their account is active.',
                'sections' => [
                    $this->section(
                        'Managing users',
                        [
                            'Use Add User to send a secure account-setup invitation instead of creating a password for somebody else.',
                            'Assign the organization role that matches the person’s responsibilities.',
                            'Use account status and archiving deliberately when somebody no longer needs normal workspace access.',
                        ]
                    ),
                    $this->section(
                        'Understanding seat usage',
                        [
                            'Your evaluation or subscription determines total user capacity and role-specific limits.',
                            'Disabled users continue to occupy seats.',
                            'Archived users release active seat capacity and can later be restored if capacity permits.',
                        ],
                        'important'
                    ),
                    $this->section(
                        'Administrative access',
                        [
                            'Organization Admin is the highest organization-management role and should be limited to trusted people.',
                            'Billing Owner is a separate assignment and does not automatically change when an organization role changes.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Settings',
                        'route' =>
                            'organization-settings.index',
                    ],
                    [
                        'label' => 'Subscription Status',
                        'route' =>
                            'organization-subscription.show',
                    ],
                    [
                        'label' => 'Billing & Receipts',
                        'route' =>
                            'organization-billing.index',
                    ],
                ],
            ],

            'users-create' => [
                'context' => 'users-create',
                'title' => 'Add user help',
                'intro' =>
                    'Use this page to invite another person into the organization and choose the access they should receive.',
                'sections' => [
                    $this->section(
                        'How invitations work',
                        [
                            'Enter the person’s real name and email address and select the organization role that matches their responsibilities.',
                            'eConsent sends the person a secure account-setup invitation.',
                            'The invited user creates their own password through the secure setup link.',
                            'Administrators never create, receive or need to know the invited user’s password.',
                        ]
                    ),
                    $this->section(
                        'Choosing the right role',
                        [
                            'Organization roles control what the user can see and do inside this organization.',
                            'Higher roles include access provided by lower roles, so assign only the access the person genuinely needs.',
                            'Review the role guide on the page before assigning elevated access.',
                        ]
                    ),
                    $this->section(
                        'Organization Admin',
                        [
                            'Organization Admin grants broad organization-management control, including users, roles, settings and organization workflows.',
                            'Assign this role only to a highly trusted person.',
                            'Assigning Organization Admin does not automatically make that person the Billing Owner.',
                        ],
                        'warning'
                    ),
                    $this->section(
                        'Seat and role limits',
                        [
                            'An invitation can be blocked if the organization has reached its total user limit or the limit for the selected role.',
                            'Disabled users continue to occupy seats while archived users release active capacity.',
                            'If there is no capacity, review existing users or your subscription before retrying.',
                        ],
                        'important'
                    ),
                    $this->section(
                        'Before sending',
                        [
                            'Double-check the email because the secure setup link is sent to that address.',
                            'Confirm the selected role is appropriate.',
                            'Confirm there is sufficient user and role capacity before sending the invitation.',
                        ]
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Settings',
                        'route' =>
                            'organization-settings.index',
                    ],
                    [
                        'label' => 'Subscription Status',
                        'route' =>
                            'organization-subscription.show',
                    ],
                ],
            ],

            'users-edit' => [
                'context' => 'users-edit',
                'title' => 'Edit user help',
                'intro' =>
                    'Use this page to update an existing organization user without bypassing access or subscription rules.',
                'sections' => [
                    $this->section(
                        'Changing access',
                        [
                            'Change the organization role only when the person’s responsibilities have changed.',
                            'Moving a user into another role can be blocked when that role has reached its subscription limit.',
                            'Review the new permissions before saving an elevated role.',
                        ]
                    ),
                    $this->section(
                        'Account status',
                        [
                            'Disabling a user prevents normal access but the account still consumes a user seat.',
                            'Archiving preserves account history while releasing active seat capacity.',
                            'Restoring an archived account later is subject to total and role-specific limits.',
                        ]
                    ),
                    $this->section(
                        'High-impact changes',
                        [
                            'Organization Admin provides broad control and should only be assigned to trusted users.',
                            'Billing ownership is separate from organization role.',
                            'The current Billing Owner may need billing ownership reassigned before certain account actions can be completed.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Settings',
                        'route' =>
                            'organization-settings.index',
                    ],
                    [
                        'label' => 'Billing & Receipts',
                        'route' =>
                            'organization-billing.index',
                    ],
                ],
            ],

            'users-archived' => [
                'context' => 'users-archived',
                'title' => 'Archived users help',
                'intro' =>
                    'Archived users are preserved for organizational history but do not occupy active user-seat capacity.',
                'sections' => [
                    $this->section(
                        'Restoring a user',
                        [
                            'Restore an account when that person needs organization access again.',
                            'The restored account returns to the organization and becomes subject to active seat limits.',
                            'Role-specific capacity also applies during restoration.',
                        ]
                    ),
                    $this->section(
                        'Before restoring',
                        [
                            'A restore can fail if all available user seats are occupied.',
                            'A restore can also fail if the user’s role has reached its role-specific limit.',
                            'Free the required capacity or review the subscription before retrying.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Settings',
                        'route' =>
                            'organization-settings.index',
                    ],
                    [
                        'label' => 'Subscription Status',
                        'route' =>
                            'organization-subscription.show',
                    ],
                ],
            ],

            'branding' => [
                'context' => 'branding',
                'title' => 'Organization branding help',
                'intro' =>
                    'Organization Branding controls the identity and contact information used by supported organization workflows.',
                'sections' => [
                    $this->section(
                        'Organization profile',
                        [
                            'Keep the organization name, email, phone number and other profile information accurate.',
                            'The organization email is used as a default or fallback email identity by supported workflows.',
                            'New signing stations can inherit organization identity defaults which remain editable for the station.',
                        ]
                    ),
                    $this->section(
                        'Before saving',
                        [
                            'These are organization-wide settings rather than personal profile settings.',
                            'Changing current branding or contact information does not rewrite historical completed-consent evidence.',
                            'Review email changes carefully because they can affect default contact and reply-to behavior for future workflows.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Settings',
                        'route' =>
                            'organization-settings.index',
                    ],
                    [
                        'label' => 'Signing Stations',
                        'route' =>
                            'signing-stations.index',
                    ],
                ],
            ],

            'subscription' => [
                'context' => 'subscription',
                'title' => 'Subscription help',
                'intro' =>
                    'Subscription Status explains the organization’s current access state, plan, billing period and usage against effective limits.',
                'sections' => [
                    $this->section(
                        'What to review',
                        [
                            'Check the current subscription state before starting workflows that depend on evaluation or paid access.',
                            'Review usage against effective limits for templates, signed consents, organization users and signing stations.',
                            'Free Evaluation limits are separate from normal paid-plan limits.',
                        ]
                    ),
                    $this->section(
                        'When access needs attention',
                        [
                            'Expired, unpaid, suspended or otherwise restricted subscription states can prevent protected organization workflows.',
                            'Billing Owners and Organization Admins can use available plan and billing controls to resolve eligible subscription issues.',
                            'Other organization users may need to contact the Billing Owner or an Organization Admin.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'View Plans',
                        'route' =>
                            'organization-subscription-plans.index',
                    ],
                    [
                        'label' => 'Billing & Receipts',
                        'route' =>
                            'organization-billing.index',
                    ],
                    [
                        'label' => 'Settings',
                        'route' =>
                            'organization-settings.index',
                    ],
                ],
            ],

            'subscription-plans' => [
                'context' => 'subscription-plans',
                'title' => 'Subscription plans help',
                'intro' =>
                    'Use this page to compare plans and start a plan-selection request when your organization needs different capacity or paid access.',
                'sections' => [
                    $this->section(
                        'Choosing a plan',
                        [
                            'Compare template, consent, user and signing-station limits against expected organization usage.',
                            'Review monthly and annual pricing before submitting a request.',
                            'Choose capacity based on operational needs rather than only current usage.',
                        ]
                    ),
                    $this->section(
                        'What happens after selection',
                        [
                            'Selecting a plan can begin a billing request and invoice workflow rather than instantly treating the organization as paid.',
                            'Use Subscription Status and Billing & Receipts to follow the resulting request and invoice state.',
                            'Successful paid access depends on the billing workflow reaching the required confirmed state.',
                        ],
                        'important'
                    ),
                    $this->section(
                        'Lower-capacity plans',
                        [
                            'Changing to a lower-capacity plan does not automatically delete existing organization data.',
                            'Existing usage can remain above the selected plan’s limits and may restrict creation of additional resources.',
                            'Review current usage before choosing a plan with lower limits.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Subscription Status',
                        'route' =>
                            'organization-subscription.show',
                    ],
                    [
                        'label' => 'Billing & Receipts',
                        'route' =>
                            'organization-billing.index',
                    ],
                ],
            ],

            'billing-management' => [
                'context' => 'billing-management',
                'title' => 'Billing management help',
                'intro' =>
                    'Billing & Receipts is the organization billing control centre for ownership, invoices, confirmed payments, receipts and reminder configuration.',
                'sections' => [
                    $this->section(
                        'Billing Owner',
                        [
                            'Exactly one active organization user acts as the Billing Owner.',
                            'The Billing Owner manages billing records, invoices, receipts, reminder settings and billing notifications.',
                            'Billing Owner is separate from Staff, Auditor, Consent Manager and Organization Admin roles.',
                        ]
                    ),
                    $this->section(
                        'Changing Billing Owner',
                        [
                            'Reassigning Billing Owner transfers the organization’s primary billing responsibility to another eligible active user.',
                            'The person’s organization role does not automatically change when billing ownership changes.',
                            'Confirm the selected person is the intended billing contact before saving.',
                        ],
                        'warning'
                    ),
                    $this->section(
                        'Invoices and payments',
                        [
                            'Issued invoices participate in the organization billing workflow; internal draft invoices are not exposed as normal payable organization invoices.',
                            'Review amount, currency, due date and payment instructions before taking payment-related action.',
                            'A reported or expected payment is not the same as a confirmed successful payment.',
                        ],
                        'important'
                    ),
                    $this->section(
                        'Receipts',
                        [
                            'After Platform Billing confirms a successful payment, an official receipt becomes available for that transaction.',
                            'Receipts can be viewed in the browser or downloaded as PDF records.',
                            'Keep transaction and receipt references when discussing a payment with billing or support staff.',
                        ]
                    ),
                    $this->section(
                        'Invoice reminders',
                        [
                            'Reminder Preferences controls whether eligible before-due and overdue reminder categories are enabled.',
                            'Reminder Recipients controls which additional eligible users receive those reminders.',
                            'Use the two settings together when changing both reminder behavior and recipient coverage.',
                        ]
                    ),
                    $this->section(
                        'Subscription relationship',
                        [
                            'Billing records document invoices, payments and receipts while Subscription Status describes the access and plan state currently applied to the organization.',
                            'An invoice being issued does not by itself mean the organization has a confirmed successful payment.',
                            'If subscription access needs attention, review both the subscription state and the related billing records before changing unrelated organization settings.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Subscription Status',
                        'route' =>
                            'organization-subscription.show',
                    ],
                    [
                        'label' => 'Reminder Preferences',
                        'route' =>
                            'organization-billing.reminder-preferences.index',
                    ],
                    [
                        'label' => 'Reminder Recipients',
                        'route' =>
                            'organization-billing.reminder-recipients.index',
                    ],
                    [
                        'label' => 'Settings',
                        'route' =>
                            'organization-settings.index',
                    ],
                ],
            ],

            'billing-invoice' => [
                'context' => 'billing-invoice',
                'title' => 'Invoice help',
                'intro' =>
                    'This page shows the organization-facing copy of an issued subscription invoice and its current billing state.',
                'sections' => [
                    $this->section(
                        'Reading the invoice',
                        [
                            'Review the invoice number, amount, currency, issue date, due date and subscription details.',
                            'Payment instructions stored with an issued invoice remain associated with that invoice record.',
                            'Use the invoice status to understand where it currently sits in the billing workflow.',
                        ]
                    ),
                    $this->section(
                        'Payment status',
                        [
                            'An invoice existing does not mean payment has been confirmed.',
                            'Reporting or initiating a payment is different from Platform Billing confirming a successful transaction.',
                            'After confirmation, use Billing & Receipts to access the resulting receipt.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Billing & Receipts',
                        'route' =>
                            'organization-billing.index',
                    ],
                    [
                        'label' => 'Subscription Status',
                        'route' =>
                            'organization-subscription.show',
                    ],
                ],
            ],

            'billing-receipt' => [
                'context' => 'billing-receipt',
                'title' => 'Receipt help',
                'intro' =>
                    'A receipt records a subscription payment that has been confirmed successfully.',
                'sections' => [
                    $this->section(
                        'Using the receipt',
                        [
                            'Review the payment amount, plan, confirmed date and transaction reference.',
                            'Download the PDF when you need an offline copy for organization records.',
                            'Use the receipt reference when discussing the confirmed transaction with billing staff.',
                        ]
                    ),
                    $this->section(
                        'Receipt versus invoice',
                        [
                            'An invoice requests or records an amount due while a receipt documents a successfully confirmed payment.',
                            'Keep both records when you need a complete billing trail.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Billing & Receipts',
                        'route' =>
                            'organization-billing.index',
                    ],
                ],
            ],

            'billing-reminder-preferences' => [
                'context' =>
                    'billing-reminder-preferences',
                'title' =>
                    'Reminder preferences help',
                'intro' =>
                    'Reminder Preferences controls which automatic invoice reminder categories the organization wants enabled.',
                'sections' => [
                    $this->section(
                        'Available reminder controls',
                        [
                            'Before-due reminders warn eligible billing recipients as an invoice approaches its due date.',
                            'Overdue reminders apply after an eligible invoice has passed its due date.',
                            'The two categories can be enabled or disabled independently.',
                        ]
                    ),
                    $this->section(
                        'What this does not change',
                        [
                            'Preferences control whether reminder categories are enabled; they do not choose every recipient.',
                            'Use Reminder Recipients to manage additional eligible people who receive invoice reminders.',
                            'Disabling reminders can reduce billing notifications, so make sure your organization has another process for tracking due invoices.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Billing & Receipts',
                        'route' =>
                            'organization-billing.index',
                    ],
                    [
                        'label' => 'Reminder Recipients',
                        'route' =>
                            'organization-billing.reminder-recipients.index',
                    ],
                ],
            ],

            'billing-reminder-recipients' => [
                'context' =>
                    'billing-reminder-recipients',
                'title' =>
                    'Reminder recipients help',
                'intro' =>
                    'Use this page to control which eligible organization users may receive invoice reminders in addition to the Billing Owner.',
                'sections' => [
                    $this->section(
                        'Choosing recipients',
                        [
                            'Only eligible users from the same organization can be configured.',
                            'Disabled, archived and foreign-organization accounts are not valid reminder recipients.',
                            'Optional recipients can be removed when the Billing Owner should remain the main destination.',
                        ]
                    ),
                    $this->section(
                        'Billing information',
                        [
                            'Invoice reminders can contain billing-related information, so add recipients only when they should receive those notifications.',
                            'Reminder Recipients controls who receives eligible reminders while Reminder Preferences controls which reminder categories are active.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Billing & Receipts',
                        'route' =>
                            'organization-billing.index',
                    ],
                    [
                        'label' => 'Reminder Preferences',
                        'route' =>
                            'organization-billing.reminder-preferences.index',
                    ],
                ],
            ],

            'templates' => [
                'context' => 'templates',
                'title' => 'Consent template help',
                'intro' =>
                    'Consent Templates contains the reusable consent content and questions available to organization workflows.',
                'sections' => [
                    $this->section(
                        'Managing templates',
                        [
                            'Use drafts for content that is still being prepared.',
                            'Publish a template before using it for live consent workflows or signing stations.',
                            'Search and status filters help separate live, unpublished and other template states.',
                        ]
                    ),
                    $this->section(
                        'Template lifecycle',
                        [
                            'Taking a template offline is different from archiving it.',
                            'Historical records continue to use the published version associated with the consent that was actually completed.',
                            'Review live workflow dependencies before taking a published template offline.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Manage Templates',
                        'route' =>
                            'consent-templates.manage',
                    ],
                    [
                        'label' => 'Create Template',
                        'route' =>
                            'consent-templates.new',
                    ],
                ],
            ],

            'templates-archived' => [
                'context' => 'templates-archived',
                'title' => 'Archived templates help',
                'intro' =>
                    'Archived Templates contains templates removed from active template management while preserving their history.',
                'sections' => [
                    $this->section(
                        'Why archive',
                        [
                            'Archive templates that should no longer appear in normal active management.',
                            'Archiving does not erase previously completed consent evidence.',
                            'A live template must be taken offline before it can be archived.',
                        ]
                    ),
                    $this->section(
                        'Restoring',
                        [
                            'Restoring returns an archived template as an offline draft.',
                            'A restored template is not automatically made live.',
                            'Review restored content before publishing it again.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Consent Templates',
                        'route' =>
                            'consent-templates.index',
                    ],
                    [
                        'label' => 'Manage Templates',
                        'route' =>
                            'consent-templates.manage',
                    ],
                ],
            ],

            'templates-new' => [
                'context' => 'templates-new',
                'title' => 'New template help',
                'intro' =>
                    'Use this page to choose the template-building workflow that best matches the consent you want to create.',
                'sections' => [
                    $this->section(
                        'Choosing a starting point',
                        [
                            'Choose the workflow that matches how you intend to collect consent.',
                            'You can build reusable content and structured questions that will later be used by signing workflows.',
                            'Give the template a purpose that will be clear to other organization users managing it later.',
                        ]
                    ),
                    $this->section(
                        'Before going live',
                        [
                            'Creating a template does not automatically make it available for live signing.',
                            'Review and publish the finished template before using it in live workflows.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Consent Templates',
                        'route' =>
                            'consent-templates.index',
                    ],
                ],
            ],

            'templates-create' => [
                'context' => 'templates-create',
                'title' => 'Create template help',
                'intro' =>
                    'Use this builder to create reusable consent content and the information you need signers to provide.',
                'sections' => [
                    $this->section(
                        'Building the consent',
                        [
                            'Use a clear title and consent wording that accurately describes what the signer is agreeing to.',
                            'Add structured questions only for information the organization actually needs to collect.',
                            'Review imported or rich-text content after editing to make sure the final consent remains readable.',
                        ]
                    ),
                    $this->section(
                        'Questions and data',
                        [
                            'Choose question types that match the expected answer, such as text, phone, yes/no, select or checkbox groups.',
                            'Question identifiers and options form part of the structured consent data, so make deliberate changes.',
                            'Avoid collecting unnecessary personal information.',
                        ],
                        'important'
                    ),
                    $this->section(
                        'Publishing',
                        [
                            'Do not publish until the wording, fields and organization requirements have been reviewed.',
                            'Once used by a live consent workflow, published content becomes part of the evidence associated with completed records.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Manage Templates',
                        'route' =>
                            'consent-templates.manage',
                    ],
                ],
            ],

            'templates-individual-create' => [
                'context' =>
                    'templates-individual-create',
                'title' =>
                    'Individual template builder help',
                'intro' =>
                    'This guided builder helps prepare a reusable template for individual consent workflows.',
                'sections' => [
                    $this->section(
                        'Building the form',
                        [
                            'Use clear consent wording and collect only information necessary for the workflow.',
                            'Select appropriate structured field types so responses are stored and displayed correctly.',
                            'Preview the resulting content before publishing.',
                        ]
                    ),
                    $this->section(
                        'Before publishing',
                        [
                            'Publishing makes the template available to supported live workflows.',
                            'Check questions, wording and required fields carefully before making the template live.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Manage Templates',
                        'route' =>
                            'consent-templates.manage',
                    ],
                ],
            ],

            'templates-manage' => [
                'context' => 'templates-manage',
                'title' => 'Template management help',
                'intro' =>
                    'Use Template Management to control which templates are live, offline, archived or available for further editing.',
                'sections' => [
                    $this->section(
                        'Managing states',
                        [
                            'Publish a reviewed template when it is ready for live consent workflows.',
                            'Take a template offline when it should temporarily stop being available for dependent workflows.',
                            'Archive an offline template when it is no longer needed in active management.',
                        ]
                    ),
                    $this->section(
                        'Live workflow impact',
                        [
                            'Taking a published template offline can make dependent signing workflows unavailable.',
                            'Signing stations using a template that goes offline can require deliberate reactivation after the template is published again.',
                            'Historical completed records remain tied to the version used when the consent was completed.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Consent Templates',
                        'route' =>
                            'consent-templates.index',
                    ],
                    [
                        'label' => 'Signing Stations',
                        'route' =>
                            'signing-stations.index',
                    ],
                ],
            ],

            'templates-edit' => [
                'context' => 'templates-edit',
                'title' => 'Edit template help',
                'intro' =>
                    'Use this page to update template content while preserving the integrity of historical consent evidence.',
                'sections' => [
                    $this->section(
                        'Editing safely',
                        [
                            'Review consent wording and structured questions carefully before saving changes.',
                            'Use appropriate question types and keep field meaning consistent when possible.',
                            'Preview the result before publishing updated content.',
                        ]
                    ),
                    $this->section(
                        'Historical records',
                        [
                            'Existing completed consent records continue to use the exact published version associated with those records.',
                            'Editing current template content should not be treated as rewriting historical consent evidence.',
                        ],
                        'important'
                    ),
                    $this->section(
                        'Live use',
                        [
                            'Changes to content intended for live use should be reviewed before publication.',
                            'Taking a template offline can affect consent workflows and signing stations that depend on it.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Manage Templates',
                        'route' =>
                            'consent-templates.manage',
                    ],
                ],
            ],

            'templates-preview' => [
                'context' => 'templates-preview',
                'title' => 'Template preview help',
                'intro' =>
                    'Preview is your final review point for consent wording, formatting and questions before live use.',
                'sections' => [
                    $this->section(
                        'What to check',
                        [
                            'Read the consent from the signer’s perspective and check that the purpose is clear.',
                            'Confirm formatting, images and question ordering are understandable.',
                            'Verify required fields and answer options are correct.',
                        ]
                    ),
                    $this->section(
                        'Before publishing',
                        [
                            'Publishing makes the reviewed version available to supported consent workflows.',
                            'Do not publish placeholder, incomplete or unapproved consent wording.',
                            'Once people sign against a published version, that version becomes part of their consent evidence.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Manage Templates',
                        'route' =>
                            'consent-templates.manage',
                    ],
                ],
            ],

            'templates-published' => [
                'context' => 'templates-published',
                'title' => 'Published template help',
                'intro' =>
                    'This is the live version of the consent template available to supported signing workflows.',
                'sections' => [
                    $this->section(
                        'Using the live consent',
                        [
                            'Use this page as the control point for starting supported consent workflows with the published template.',
                            'The published version is the content signers should receive when the workflow is started from this template.',
                            'Review workflow options before distributing the consent.',
                        ]
                    ),
                    $this->section(
                        'Taking it offline',
                        [
                            'Taking the template offline can interrupt new workflows that depend on this live template.',
                            'Signing stations assigned to an offline template can become unavailable until the template is republished and the station is deliberately reactivated where required.',
                            'Completed historical records remain preserved.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Consent Records',
                        'route' =>
                            'consent-sessions.index',
                    ],
                    [
                        'label' => 'Signing Stations',
                        'route' =>
                            'signing-stations.index',
                    ],
                ],
            ],

            'templates-history' => [
                'context' => 'templates-history',
                'title' => 'Template version history help',
                'intro' =>
                    'Version History helps you understand how a consent template has changed over time.',
                'sections' => [
                    $this->section(
                        'Understanding versions',
                        [
                            'Published versions preserve the content associated with consent activity at that point in time.',
                            'Use version history when reviewing changes to wording, questions or other template content.',
                            'Historical versions help explain which content belonged to older consent records.',
                        ]
                    ),
                    $this->section(
                        'Evidence preservation',
                        [
                            'Do not assume the current editable template represents the content used by every historical consent.',
                            'Completed consent records retain their relationship to the version that was actually used.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Manage Templates',
                        'route' =>
                            'consent-templates.manage',
                    ],
                ],
            ],

            'consent-records' => [
                'context' => 'consent-records',
                'title' => 'Consent record help',
                'intro' =>
                    'Consent Records contains signing status, captured responses and evidence for consent workflows.',
                'sections' => [
                    $this->section(
                        'Using the register',
                        [
                            'Use filters and search to narrow the records you are reviewing.',
                            'Check record status to distinguish pending and completed workflows.',
                            'Open an individual record when you need detailed signer information or audit evidence.',
                        ]
                    ),
                    $this->section(
                        'Downloads and exports',
                        [
                            'Signed PDF download and structured data export are separate actions.',
                            'Use PDF output when you need the completed signed document or register-style PDF output.',
                            'Use Export data when you need selected structured fields from the current matching record scope.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Export Data',
                        'route' =>
                            'consent-sessions.export-data',
                    ],
                    [
                        'label' => 'Consent Templates',
                        'route' =>
                            'consent-templates.index',
                    ],
                ],
            ],

            'consent-select-template' => [
                'context' => 'consent-select-template',
                'title' => 'Choose consent template help',
                'intro' =>
                    'Choose the published template that contains the consent wording and questions you want this individual workflow to use.',
                'sections' => [
                    $this->section(
                        'Choosing correctly',
                        [
                            'Select the template whose wording matches the purpose of the consent you are about to send or complete.',
                            'Only use a template that has been reviewed for this workflow.',
                            'If no suitable template exists, return to Template Management and prepare the correct consent first.',
                        ]
                    ),
                    $this->section(
                        'Why selection matters',
                        [
                            'The selected published version becomes part of the consent record created by the workflow.',
                            'Choose carefully because historical completed records preserve the version actually used.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Consent Templates',
                        'route' =>
                            'consent-templates.index',
                    ],
                    [
                        'label' => 'Consent Records',
                        'route' =>
                            'consent-sessions.index',
                    ],
                ],
            ],

            'consent-create' => [
                'context' => 'consent-create',
                'title' => 'Start consent help',
                'intro' =>
                    'Use this page to start an individual consent workflow from the selected published template.',
                'sections' => [
                    $this->section(
                        'Before sending',
                        [
                            'Confirm that you selected the correct template and signer information.',
                            'Review any delivery information before starting the workflow.',
                            'Check email addresses carefully when the workflow will send consent email.',
                        ]
                    ),
                    $this->section(
                        'Creating the record',
                        [
                            'Starting the workflow creates the consent record that will track signing activity and evidence.',
                            'The record preserves the published template version associated with this workflow.',
                            'Avoid creating duplicate workflows accidentally for the same intended consent event.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Consent Records',
                        'route' =>
                            'consent-sessions.index',
                    ],
                ],
            ],

            'consent-record' => [
                'context' => 'consent-record',
                'title' => 'Consent record details help',
                'intro' =>
                    'This page shows the status, captured information and evidence belonging to one consent workflow.',
                'sections' => [
                    $this->section(
                        'Reviewing the record',
                        [
                            'Check the status before deciding whether any follow-up action is appropriate.',
                            'Review signer responses and the template-derived fields shown for this exact record.',
                            'Use the audit trail when you need chronological evidence about the signing process.',
                        ]
                    ),
                    $this->section(
                        'Completed evidence',
                        [
                            'Completed records can include a generated signed PDF representing the consent produced at completion.',
                            'The record uses the exact published template version associated with this workflow.',
                            'Current template edits do not rewrite this historical record.',
                        ],
                        'important'
                    ),
                    $this->section(
                        'Record actions',
                        [
                            'Cancellation and manual delivery actions affect a real consent workflow, so confirm the record and recipient before continuing.',
                            'Do not resend signed documents or reminders to an unintended email address.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Consent Records',
                        'route' =>
                            'consent-sessions.index',
                    ],
                ],
            ],

            'consent-export-data' => [
                'context' => 'consent-export-data',
                'title' => 'Export consent data help',
                'intro' =>
                    'Export Data creates a structured register from the consent records currently inside your selected scope.',
                'sections' => [
                    $this->section(
                        'Choosing export columns',
                        [
                            'Only columns containing data somewhere in the current matching record scope are offered.',
                            'Signer name, email and phone are selected by default when those columns are available.',
                            'You can change the selected columns before creating the export.',
                            'Template filters affect both the matching records and the columns available for export.',
                        ]
                    ),
                    $this->section(
                        'Choosing a format',
                        [
                            'PDF register is useful for a readable record summary.',
                            'CSV and XLSX are better suited to structured analysis or transfer into spreadsheet tools.',
                            'PDF download of signed consent documents remains separate from structured data export.',
                        ]
                    ),
                    $this->section(
                        'Personal information',
                        [
                            'Exports may contain names, contact information and consent responses.',
                            'Download only the fields you need and store exported files according to your organization’s data-handling requirements.',
                            'Be especially careful when forwarding CSV, XLSX or PDF exports outside the organization.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Consent Records',
                        'route' =>
                            'consent-sessions.index',
                    ],
                ],
            ],

            'consent-audit' => [
                'context' => 'consent-audit',
                'title' => 'Consent audit trail help',
                'intro' =>
                    'The audit trail provides chronological evidence associated with a consent workflow.',
                'sections' => [
                    $this->section(
                        'Reading the audit trail',
                        [
                            'Use timestamps and event descriptions to understand the sequence of consent activity.',
                            'Review the trail together with the consent record and completed PDF when investigating a workflow.',
                            'The audit trail supports evidence review rather than replacing the consent document itself.',
                        ]
                    ),
                    $this->section(
                        'Evidence handling',
                        [
                            'Treat audit information as part of the organization’s consent evidence.',
                            'Avoid copying or exposing audit information unnecessarily when it contains signer or workflow details.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Consent Records',
                        'route' =>
                            'consent-sessions.index',
                    ],
                ],
            ],

            'campaigns' => [
                'context' => 'campaigns',
                'title' => 'Bulk consent campaign help',
                'intro' =>
                    'Bulk Consent Campaigns let you create independent consent records for multiple recipients from one published template.',
                'sections' => [
                    $this->section(
                        'Using campaigns',
                        [
                            'Each recipient receives an independent consent workflow and gets their own consent record.',
                            'Use the campaign list to review campaign status and return to existing recipient batches.',
                            'Choose a campaign when the same approved consent needs to be distributed to several people.',
                        ]
                    ),
                    $this->section(
                        'Email and capacity',
                        [
                            'Campaign delivery can generate multiple outgoing emails because each recipient receives an independent workflow.',
                            'Campaign availability and email behavior can depend on the organization’s subscription or evaluation state.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Create Campaign',
                        'route' =>
                            'consent-campaigns.select-template',
                    ],
                    [
                        'label' => 'Consent Records',
                        'route' =>
                            'consent-sessions.index',
                    ],
                ],
            ],

            'campaign-select-template' => [
                'context' => 'campaign-select-template',
                'title' => 'Choose campaign template help',
                'intro' =>
                    'Choose the published consent template that every recipient in this campaign should receive.',
                'sections' => [
                    $this->section(
                        'Selecting a template',
                        [
                            'Choose a template whose wording applies consistently to every intended campaign recipient.',
                            'Review the live template before distributing it at scale.',
                            'If the correct consent does not exist, create or update the template before building the campaign.',
                        ]
                    ),
                    $this->section(
                        'Campaign impact',
                        [
                            'A campaign creates independent records for its recipients using the selected consent.',
                            'Selecting the wrong template can distribute incorrect consent wording to multiple people.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Consent Templates',
                        'route' =>
                            'consent-templates.index',
                    ],
                    [
                        'label' => 'Campaigns',
                        'route' =>
                            'consent-campaigns.index',
                    ],
                ],
            ],

            'campaign-create' => [
                'context' => 'campaign-create',
                'title' => 'Create campaign help',
                'intro' =>
                    'Use this page to add recipients and send a published consent as independent workflows.',
                'sections' => [
                    $this->section(
                        'Recipients',
                        [
                            'A campaign accepts between 1 and 20 recipients; 20 is the maximum, not a required count.',
                            'Check every email address before sending.',
                            'Duplicate recipient email addresses are rejected.',
                            'CSV import can be used when the recipient data is prepared in the supported structure.',
                        ]
                    ),
                    $this->section(
                        'Deadline',
                        [
                            'A campaign deadline is optional.',
                            'If you provide a deadline time, provide the corresponding deadline date.',
                            'Choose a deadline that is appropriate for the consent request.',
                        ]
                    ),
                    $this->section(
                        'Before sending',
                        [
                            'Sending the campaign creates an independent consent record and email workflow for each recipient.',
                            'A mistake in recipient addresses or template choice can therefore affect several people at once.',
                            'Review the template, recipient list and deadline before submitting.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Campaigns',
                        'route' =>
                            'consent-campaigns.index',
                    ],
                    [
                        'label' => 'Consent Records',
                        'route' =>
                            'consent-sessions.index',
                    ],
                ],
            ],

            'campaign-show' => [
                'context' => 'campaign-show',
                'title' => 'Campaign details help',
                'intro' =>
                    'Campaign Details shows the distribution state of the campaign and its independent recipient consent records.',
                'sections' => [
                    $this->section(
                        'Tracking recipients',
                        [
                            'Review individual recipient status instead of assuming the whole campaign has one shared signing state.',
                            'Each recipient corresponds to an independent consent workflow.',
                            'Use the linked consent records when you need the detailed evidence for one recipient.',
                        ]
                    ),
                    $this->section(
                        'Follow-up',
                        [
                            'Follow-up should be based on the individual recipient’s actual status.',
                            'Be careful not to send unnecessary or duplicate communication to recipients who have already completed their consent.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Campaigns',
                        'route' =>
                            'consent-campaigns.index',
                    ],
                    [
                        'label' => 'Consent Records',
                        'route' =>
                            'consent-sessions.index',
                    ],
                ],
            ],

            'signing-stations' => [
                'context' => 'signing-stations',
                'title' => 'Signing station help',
                'intro' =>
                    'Signing Stations provide reusable kiosk or QR access to published consent workflows.',
                'sections' => [
                    $this->section(
                        'Managing stations',
                        [
                            'A station needs a published consent template before it can be activated for signing.',
                            'Use station status to understand whether the public workflow is currently available.',
                            'Open an individual station to manage its configuration and QR access.',
                        ]
                    ),
                    $this->section(
                        'Capacity and template state',
                        [
                            'Active station capacity is controlled by the organization’s effective subscription limit.',
                            'If the assigned template goes offline, the station can become unavailable and may require deliberate reactivation after republishing.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Create Signing Station',
                        'route' =>
                            'signing-stations.create',
                    ],
                    [
                        'label' => 'Station Analytics',
                        'route' =>
                            'signing-stations.analytics',
                    ],
                ],
            ],

            'signing-stations-create' => [
                'context' => 'signing-stations-create',
                'title' => 'Create signing station help',
                'intro' =>
                    'Use this page to create a kiosk or QR signing point using a published consent template.',
                'sections' => [
                    $this->section(
                        'Initial setup',
                        [
                            'Choose the published consent template the station should use.',
                            'Configure the station name and delivery identity so organization staff can recognize its purpose.',
                            'Organization email identity can provide defaults for supported email settings, which remain editable.',
                        ]
                    ),
                    $this->section(
                        'Capacity',
                        [
                            'Creating or activating a station is subject to the organization’s active signing-station limit.',
                            'If capacity is full, pause another station or review subscription capacity before activating another one.',
                        ],
                        'warning'
                    ),
                    $this->section(
                        'Before sharing QR access',
                        [
                            'Finish and review the station configuration before printing or distributing QR material.',
                            'Future configuration changes can rotate public access details in situations where the station identity must be protected.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Signing Stations',
                        'route' =>
                            'signing-stations.index',
                    ],
                    [
                        'label' => 'Consent Templates',
                        'route' =>
                            'consent-templates.index',
                    ],
                ],
            ],

            'signing-stations-show' => [
                'context' => 'signing-stations-show',
                'title' => 'Signing station details help',
                'intro' =>
                    'This page is the control centre for one signing station, including its status and public access information.',
                'sections' => [
                    $this->section(
                        'Operating the station',
                        [
                            'Confirm the assigned template is live before expecting the station to accept new signing workflows.',
                            'Use pause and activation controls deliberately when changing station availability.',
                            'QR expiry affects new QR scans rather than automatically ending every already-configured shared kiosk workflow.',
                        ]
                    ),
                    $this->section(
                        'QR material',
                        [
                            'Only distribute the current QR code or poster for this station.',
                            'If station configuration rotates the public token, previously printed QR material can stop working.',
                            'Replace old printed material after a QR-changing configuration update.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Signing Stations',
                        'route' =>
                            'signing-stations.index',
                    ],
                    [
                        'label' => 'Station Analytics',
                        'route' =>
                            'signing-stations.analytics',
                    ],
                ],
            ],

            'signing-stations-edit' => [
                'context' => 'signing-stations-edit',
                'title' => 'Edit signing station help',
                'intro' =>
                    'Use this page to change station configuration while understanding the effect on public QR access and active device leases.',
                'sections' => [
                    $this->section(
                        'Editing configuration',
                        [
                            'Review station name, assigned template and email identity before saving.',
                            'Changing the assigned template changes the consent people will receive through this station.',
                            'Saving genuinely unchanged station details should preserve the current QR access.',
                        ]
                    ),
                    $this->section(
                        'QR and device impact',
                        [
                            'Changing station details that affect public access rotates the public token.',
                            'Changing the assigned template also rotates the public QR token.',
                            'A rotation invalidates old QR access and releases existing device leases, so printed QR material may need replacement.',
                        ],
                        'warning'
                    ),
                    $this->section(
                        'Template availability',
                        [
                            'The station depends on a published template.',
                            'If the assigned template is offline, the station cannot operate normally until the template is republished and the station is reactivated where required.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Signing Stations',
                        'route' =>
                            'signing-stations.index',
                    ],
                    [
                        'label' => 'Consent Templates',
                        'route' =>
                            'consent-templates.index',
                    ],
                ],
            ],

            'signing-stations-analytics' => [
                'context' =>
                    'signing-stations-analytics',
                'title' =>
                    'Signing station analytics help',
                'intro' =>
                    'Station Analytics summarizes signing-station activity so you can understand how stations are being used.',
                'sections' => [
                    $this->section(
                        'Reading analytics',
                        [
                            'Use the available totals and breakdowns to compare station activity.',
                            'Apply the available filters when you need a narrower view of usage.',
                            'Treat analytics as an operational summary rather than a substitute for individual consent evidence.',
                        ]
                    ),
                    $this->section(
                        'Export and privacy',
                        [
                            'Export analytics only when you need an offline operational record or further analysis.',
                            'Protect exported files if they contain organization or workflow information that should not be broadly shared.',
                        ],
                        'important'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Signing Stations',
                        'route' =>
                            'signing-stations.index',
                    ],
                    [
                        'label' => 'Consent Records',
                        'route' =>
                            'consent-sessions.index',
                    ],
                ],
            ],

            'profile' => [
                'context' => 'profile',
                'title' => 'Profile & account help',
                'intro' =>
                    'Profile contains your personal account information and security-sensitive account actions.',
                'sections' => [
                    $this->section(
                        'Profile information',
                        [
                            'Keep your personal name and email address accurate.',
                            'Changing account information affects your user account rather than the organization profile.',
                            'Organization contact information is managed separately in Organization Branding.',
                        ]
                    ),
                    $this->section(
                        'Password',
                        [
                            'Use a strong password that you do not reuse on unrelated services.',
                            'Do not share your password with administrators or other organization users.',
                            'Google-linked authentication and password authentication should both remain subject to normal account-access protections.',
                        ],
                        'important'
                    ),
                    $this->section(
                        'Deleting your account',
                        [
                            'Account deletion is a high-impact action and requires password confirmation where supported.',
                            'Review the effect on your organization responsibilities before deleting an account, especially if you hold administrative or billing responsibilities.',
                        ],
                        'warning'
                    ),
                ],
                'links' => [
                    [
                        'label' => 'Settings',
                        'route' =>
                            'organization-settings.index',
                    ],
                    [
                        'label' => 'Organization Branding',
                        'route' =>
                            'organization-branding.edit',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<int, string> $tips
     *
     * @return array{
     *     title: string,
     *     type: string,
     *     tips: array<int, string>
     * }
     */
    private function section(
        string $title,
        array $tips,
        string $type = 'guide'
    ): array {
        return [
            'title' => $title,
            'type' => $type,
            'tips' => $tips,
        ];
    }

    /**
     * @return array<int, array{
     *     label: string,
     *     route: string
     * }>
     */
    private function defaultLinks(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
            ],
            [
                'label' => 'Consent Templates',
                'route' =>
                    'consent-templates.index',
            ],
            [
                'label' => 'Consent Records',
                'route' =>
                    'consent-sessions.index',
            ],
            [
                'label' => 'Signing Stations',
                'route' =>
                    'signing-stations.index',
            ],
            [
                'label' => 'Settings',
                'route' =>
                    'organization-settings.index',
            ],
        ];
    }
}
