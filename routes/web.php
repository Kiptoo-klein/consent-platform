<?php

use App\Http\Controllers\SigningStationAnalyticsController;

use App\Http\Controllers\ConsentNotificationController;
use App\Http\Controllers\ConsentSessionController;
use App\Http\Controllers\ConsentPdfDeliveryController;
use App\Http\Controllers\ConsentBulkDownloadController;
use App\Http\Controllers\ConsentTemplateCategoryController;
use App\Http\Controllers\ConsentTemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganizationBrandingController;
use App\Http\Controllers\OrganizationBillingController;
use App\Http\Controllers\OrganizationSubscriptionController;
use App\Http\Controllers\OrganizationSubscriptionPlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicConsentSigningController;
use App\Http\Controllers\PublicSigningStationController;
use App\Http\Controllers\SigningStationController;
use App\Http\Controllers\SigningStationDeviceLeaseController;
use App\Http\Controllers\Platform\ProductionReadinessController;
use App\Http\Controllers\EmailDiagnosticsController;
use App\Http\Controllers\SecurityStatusController;
use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\PlatformBillingController;
use App\Http\Controllers\Platform\OrganizationController;
use App\Http\Controllers\Platform\SubscriptionInvoiceController;
use App\Http\Controllers\Platform\SubscriptionInvoiceReminderSettingsController;
use App\Http\Controllers\Platform\SubscriptionPaymentSettingsController;
use App\Http\Controllers\Platform\PlatformBrandingSettingsController;
use App\Http\Controllers\Platform\PlatformStaffController;
use App\Http\Controllers\Platform\SubscriptionTransactionController;
use App\Http\Controllers\Platform\PlatformOrganizationUserController;
use App\Http\Controllers\Platform\PlatformActivityLogController;
use App\Http\Controllers\ConsentPdfController;
use App\Http\Controllers\ConsentAuditController;
use App\Http\Controllers\Platform\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| These routes do not require the user to log in.
| Signers use them to access consent records and signing stations.
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Public Signing Station Routes
|--------------------------------------------------------------------------
|
| Signing-station users first see the welcome page, review the published
| consent document, confirm that they have read it, enter their details,
| and then continue to the signature step.
|
| The controller manually looks up the station using the station_token
| database column, so these routes use a normal string parameter.
|
*/

Route::get('/station/{stationToken}', [
    PublicSigningStationController::class,
    'show',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'kiosk.device',
        'throttle:public-station-view',
    ])
    ->name('public-signing-stations.show');

Route::post('/station/{stationToken}/heartbeat', [
    SigningStationDeviceLeaseController::class,
    'heartbeat',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'kiosk.device',
        'throttle:public-station-action',
    ])
    ->name('public-signing-stations.heartbeat');


Route::get('/station/{stationToken}/review', [
    PublicSigningStationController::class,
    'review',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'kiosk.device',
        'throttle:public-station-view',
    ])
    ->name('public-signing-stations.review');

Route::post('/station/{stationToken}/continue', [
    PublicSigningStationController::class,
    'confirmReview',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'kiosk.device',
        'throttle:public-station-action',
    ])
    ->name('public-signing-stations.continue');

Route::get('/station/{stationToken}/details', [
    PublicSigningStationController::class,
    'details',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'kiosk.device',
        'throttle:public-station-view',
    ])
    ->name('public-signing-stations.details');

Route::post('/station/{stationToken}/start', [
    PublicSigningStationController::class,
    'start',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'kiosk.device',
        'throttle:public-station-action',
    ])
    ->name('public-signing-stations.start');

Route::post('/station/{stationToken}/cancel', [
    PublicSigningStationController::class,
    'cancel',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'kiosk.device',
        'throttle:public-station-action',
    ])
    ->name('public-signing-stations.cancel');

/*
|--------------------------------------------------------------------------
| Public Consent Signing Routes
|--------------------------------------------------------------------------
|
| These routes handle consent records that already exist. They remain
| available for signing-station records and records created manually
| through the authenticated application.
|
*/

Route::get('/sign/{accessToken}', [
    PublicConsentSigningController::class,
    'show',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
        'kiosk.device',
        'throttle:public-consent-view',
    ])
    ->name('public-consent.show');

Route::patch('/sign/{accessToken}', [
    PublicConsentSigningController::class,
    'update',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
        'kiosk.device',
        'throttle:public-consent-write',
    ])
    ->name('public-consent.update');

Route::get('/sign/{accessToken}/signature', [
    PublicConsentSigningController::class,
    'signature',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
        'kiosk.device',
        'throttle:public-consent-view',
    ])
    ->name('public-consent.signature');

Route::post('/sign/{accessToken}/complete', [
    PublicConsentSigningController::class,
    'complete',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
        'kiosk.device',
        'throttle:public-signature-submit',
        \App\Http\Middleware\HardenSignatureSubmission::class,
    ])
    ->name('public-consent.complete');

Route::post('/sign/{accessToken}/cancel', [
    PublicConsentSigningController::class,
    'cancel',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
        'kiosk.device',
        'throttle:public-consent-write',
    ])
    ->name('public-consent.cancel');

Route::get('/sign/{accessToken}/completed', [
    PublicConsentSigningController::class,
    'completed',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
        'kiosk.device',
        'throttle:public-consent-view',
    ])
    ->name('public-consent.completed');

/*
|--------------------------------------------------------------------------
| Organization Subscription Status
|--------------------------------------------------------------------------
|
| This route remains available when organization workflow access is
| blocked because payment is required.
|
*/

Route::middleware([
    'auth',
    'active.user',
    'organization.user',
])->group(function () {
    Route::get('/organization/subscription', [
        OrganizationSubscriptionController::class,
        'show',
    ])->name('organization-subscription.show');


    Route::get('/subscription/plans', [
        OrganizationSubscriptionPlanController::class,
        'index',
    ])->name(
        'organization-subscription-plans.index'
    );

    Route::post(
        '/subscription/plans/{subscriptionPlan}/request',
        [
            OrganizationSubscriptionPlanController::class,
            'store',
        ]
    )->name(
        'organization-subscription-plans.request'
    );



    Route::delete(
        '/subscription/plans/requests/{planRequest}',
        [
            OrganizationSubscriptionPlanController::class,
            'cancel',
        ]
    )->name(
        'organization-subscription-plans.cancel'
    );

    Route::get('/subscription/billing', [
        OrganizationBillingController::class,
        'index',
    ])->name('organization-billing.index');


    Route::patch('/subscription/billing/owner', [
        OrganizationBillingController::class,
        'updateBillingOwner',
    ])
        ->middleware(
            'organization.role:organization_administrator'
        )
        ->name('organization-billing.owner.update');

    Route::get(
        '/subscription/billing/reminder-preferences',
        [
            OrganizationBillingController::class,
            'reminderPreferences',
        ]
    )->name(
        'organization-billing.reminder-preferences.index'
    );

    Route::patch(
        '/subscription/billing/reminder-preferences',
        [
            OrganizationBillingController::class,
            'updateReminderPreferences',
        ]
    )->name(
        'organization-billing.reminder-preferences.update'
    );

    Route::get(
        '/subscription/billing/reminder-recipients',
        [
            OrganizationBillingController::class,
            'reminderRecipients',
        ]
    )->name(
        'organization-billing.reminder-recipients.index'
    );

    Route::patch(
        '/subscription/billing/reminder-recipients',
        [
            OrganizationBillingController::class,
            'updateReminderRecipients',
        ]
    )->name(
        'organization-billing.reminder-recipients.update'
    );

    Route::get(
        '/subscription/billing/invoices/{subscriptionInvoice}',
        [
            OrganizationBillingController::class,
            'showInvoice',
        ]
    )->name('organization-billing.invoices.show');

    Route::get(
        '/subscription/billing/invoices/{subscriptionInvoice}/download',
        [
            OrganizationBillingController::class,
            'downloadInvoice',
        ]
    )->name('organization-billing.invoices.download');

    Route::get(
        '/subscription/billing/transactions/{subscriptionTransaction}',
        [
            OrganizationBillingController::class,
            'show',
        ]
    )->name('organization-billing.receipts.show');

    Route::get(
        '/subscription/billing/transactions/{subscriptionTransaction}/download',
        [
            OrganizationBillingController::class,
            'download',
        ]
    )->name('organization-billing.receipts.download');

    /*
    |--------------------------------------------------------------------------
    | Organization Administrator User Management
    |--------------------------------------------------------------------------
    |
    | Organization Administrators may manage users belonging only to their
    | own organization. The platform controller is reused so subscription
    | seat limits and administrator safety rules remain consistent.
    |
    */

    Route::middleware(
        'organization.role:organization_administrator'
    )
        ->prefix('/organization/{organization}/users')
        ->name('organization-users.')
        ->group(function () {
            Route::get(
                '/',
                [
                    PlatformOrganizationUserController::class,
                    'index',
                ]
            )->name('index');

            Route::get(
                '/archived',
                [
                    PlatformOrganizationUserController::class,
                    'archived',
                ]
            )->name('archived');

            Route::get(
                '/create',
                [
                    PlatformOrganizationUserController::class,
                    'create',
                ]
            )->name('create');

            Route::post(
                '/',
                [
                    PlatformOrganizationUserController::class,
                    'store',
                ]
            )->name('store');

            Route::get(
                '/{user}/edit',
                [
                    PlatformOrganizationUserController::class,
                    'edit',
                ]
            )->name('edit');

            Route::put(
                '/{user}',
                [
                    PlatformOrganizationUserController::class,
                    'update',
                ]
            )->name('update');

            Route::patch(
                '/{user}/status',
                [
                    PlatformOrganizationUserController::class,
                    'updateStatus',
                ]
            )->name('status');

            Route::delete(
                '/{user}',
                [
                    PlatformOrganizationUserController::class,
                    'destroy',
                ]
            )->name('destroy');

            Route::patch(
                '/{user}/restore',
                [
                    PlatformOrganizationUserController::class,
                    'restore',
                ]
            )
                ->withTrashed()
                ->name('restore');
        });

});

/*
|--------------------------------------------------------------------------
| Authenticated Application Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'active.user',
    'organization.user',
    'organization.subscription',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        DashboardController::class,
        'index',
    ])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Organization Branding
    |--------------------------------------------------------------------------
    */

    Route::get('/organization/branding', [
        OrganizationBrandingController::class,
        'edit',
    ])
        ->middleware(
            'organization.role:organization_administrator'
        )
        ->name('organization-branding.edit');

    Route::put('/organization/branding', [
        OrganizationBrandingController::class,
        'update',
    ])
        ->middleware(
            'organization.role:organization_administrator'
        )
        ->name('organization-branding.update');

   /*
|--------------------------------------------------------------------------
| Consent Templates
|--------------------------------------------------------------------------
*/

Route::get('/consent-templates', [
    ConsentTemplateController::class,
    'index',
])->name('consent-templates.index');

Route::get('/consent-templates/new', [
    ConsentTemplateController::class,
    'chooser',
])->name('consent-templates.new');

Route::get('/consent-templates/manage', [
    ConsentTemplateController::class,
    'manage',
])->name('consent-templates.manage');


Route::get('/consent-templates/archived', [
    ConsentTemplateController::class,
    'archived',
])->name('consent-templates.archived');

Route::get('/consent-templates/create/individual', [
    \App\Http\Controllers\IndividualConsentWizardController::class,
    'create',
])->name('consent-templates.individual.create');

Route::post('/consent-templates/create/individual', [
    \App\Http\Controllers\IndividualConsentWizardController::class,
    'store',
])->name('consent-templates.individual.store');

Route::get('/consent-templates/create', [
    ConsentTemplateController::class,
    'create',
])->name('consent-templates.create');

Route::post('/consent-templates', [
    ConsentTemplateController::class,
    'store',
])->name('consent-templates.store');

Route::get('/consent-templates/{consentTemplate}/edit', [
    ConsentTemplateController::class,
    'edit',
])->name('consent-templates.edit');

Route::put('/consent-templates/{consentTemplate}', [
    ConsentTemplateController::class,
    'update',
])->name('consent-templates.update');

Route::patch('/consent-template-categories', [
    ConsentTemplateCategoryController::class,
    'update',
])->name('consent-template-categories.update');

/*
|--------------------------------------------------------------------------
| Working Draft Preview
|--------------------------------------------------------------------------
*/

Route::get('/consent-templates/{consentTemplate}/preview', [
    ConsentTemplateController::class,
    'preview',
])->name('consent-templates.preview');

/*
|--------------------------------------------------------------------------
| Publishing
|--------------------------------------------------------------------------
*/

Route::post('/consent-templates/{consentTemplate}/publish', [
    ConsentTemplateController::class,
    'publish',
])->name('consent-templates.publish');


Route::post('/consent-templates/{consentTemplate}/unpublish', [
    ConsentTemplateController::class,
    'unpublish',
])->name('consent-templates.unpublish');


Route::post('/consent-templates/{consentTemplate}/archive', [
    ConsentTemplateController::class,
    'archive',
])->name('consent-templates.archive');


Route::patch('/consent-templates/{consentTemplate}/restore', [
    ConsentTemplateController::class,
    'restore',
])->name('consent-templates.restore');

/*
|--------------------------------------------------------------------------
| Published Version
|--------------------------------------------------------------------------
*/

Route::get('/consent-templates/{consentTemplate}/published', [
    ConsentTemplateController::class,
    'showPublished',
])->name('consent-templates.published');

/*
|--------------------------------------------------------------------------
| Version History
|--------------------------------------------------------------------------
*/
Route::get('/consent-templates/{consentTemplate}/versions', [
    ConsentTemplateController::class,
    'history',
])->name('consent-templates.history');
    /*
|--------------------------------------------------------------------------
| Consent Records
|--------------------------------------------------------------------------
*/

Route::get('/consent-campaigns', [
    \App\Http\Controllers\ConsentCampaignController::class,
    'index',
])->name('consent-campaigns.index');

Route::get(
    '/consent-templates/{consentTemplate}/consent-campaigns/create',
    [
        \App\Http\Controllers\ConsentCampaignController::class,
        'create',
    ]
)->name('consent-campaigns.create');

Route::post(
    '/consent-templates/{consentTemplate}/consent-campaigns',
    [
        \App\Http\Controllers\ConsentCampaignController::class,
        'store',
    ]
)->name('consent-campaigns.store');

Route::get('/consent-campaigns/{consentCampaign}', [
    \App\Http\Controllers\ConsentCampaignController::class,
    'show',
])->whereNumber('consentCampaign')
    ->name('consent-campaigns.show');

Route::get('/consent-campaigns/create', [
    \App\Http\Controllers\ConsentCampaignController::class,
    'selectTemplate',
])->name('consent-campaigns.select-template');

Route::get('/consent-records', [
    ConsentSessionController::class,
    'index',
])->name('consent-sessions.index');

Route::get('/consent-records/create', [
    ConsentSessionController::class,
    'selectTemplate',
])->name('consent-sessions.select-template');

Route::get('/consent-records/download-all', [
    ConsentBulkDownloadController::class,
    'download',
])->name('consent-sessions.download-all');

Route::get(
    '/consent-templates/{consentTemplate}/consent-records/create',
    [
        ConsentSessionController::class,
        'create',
    ]
)->name('consent-sessions.create');

Route::post(
    '/consent-templates/{consentTemplate}/consent-records',
    [
        ConsentSessionController::class,
        'store',
    ]
)->name('consent-sessions.store');

Route::get('/consent-records/{consentSession}/pdf', [
    ConsentPdfController::class,
    'download',
])->name('consent-sessions.pdf');

Route::get('/consent-records/{consentSession}', [
    ConsentSessionController::class,
    'show',
])->name('consent-sessions.show');

Route::patch('/consent-records/{consentSession}/cancel', [
    ConsentSessionController::class,
    'cancel',
])->name('consent-sessions.cancel');

Route::post('/consent-records/{consentSession}/email', [
    ConsentNotificationController::class,
    'send',
])->name('consent-sessions.email.send');

Route::post('/consent-records/{consentSession}/email/reminder', [
    ConsentNotificationController::class,
    'remind',
])->name('consent-sessions.email.reminder');

Route::post(
    '/consent-records/{consentSession}/signed-pdf-delivery/retry',
    [ConsentPdfDeliveryController::class, 'retry']
)->name('consent-sessions.signed-pdf.retry');

Route::get(
    '/consent-sessions/{consentSession}/audit-trail',
    [ConsentAuditController::class, 'show']
)->name('consent-sessions.audit');
    /*
    |--------------------------------------------------------------------------
    | Signing Stations
    |--------------------------------------------------------------------------
    */

    Route::get('/signing-stations', [
        SigningStationController::class,
        'index',
    ])->name('signing-stations.index');

    Route::get('/signing-stations/create', [
        SigningStationController::class,
        'create',
    ])->name('signing-stations.create');

    Route::post('/signing-stations', [
        SigningStationController::class,
        'store',
    ])->name('signing-stations.store');

    /* KIOSK_ANALYTICS_ROUTES */
    Route::get('/signing-stations/analytics/export', [
        SigningStationAnalyticsController::class,
        'export',
    ])->name('signing-stations.analytics.export');

    Route::get('/signing-stations/analytics', [
        SigningStationAnalyticsController::class,
        'index',
    ])->name('signing-stations.analytics');

    Route::get('/signing-stations/{signingStation}', [
        SigningStationController::class,
        'show',
    ])->name('signing-stations.show');

    Route::get('/signing-stations/{signingStation}/edit', [
        SigningStationController::class,
        'edit',
    ])->name('signing-stations.edit');

    Route::put('/signing-stations/{signingStation}', [
        SigningStationController::class,
        'update',
    ])->name('signing-stations.update');

    Route::patch('/signing-stations/{signingStation}/toggle', [
        SigningStationController::class,
        'toggle',
    ])->name('signing-stations.toggle');

    Route::post('/signing-stations/{signingStation}/regenerate-token', [
        SigningStationController::class,
        'regenerateToken',
    ])->name('signing-stations.regenerate-token');

    Route::get('/signing-stations/{signingStation}/qr-code', [
        SigningStationController::class,
        'qrCode',
    ])->name('signing-stations.qr-code');

    Route::get('/signing-stations/{signingStation}/qr-code/download', [
        SigningStationController::class,
        'downloadQrCode',
    ])->name('signing-stations.qr-code.download');

});

/*
|--------------------------------------------------------------------------
| Authenticated Profile Routes
|--------------------------------------------------------------------------
|
| Both organization users and platform administrators may manage
| their own profile. These routes therefore do not use organization.user.
|
*/

Route::middleware([
    'auth',
    'active.user',
])->group(function () {

    Route::get('/profile', [
        ProfileController::class,
        'edit',
    ])->name('profile.edit');

    Route::patch('/profile', [
        ProfileController::class,
        'update',
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy',
    ])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Platform Administration Routes
|--------------------------------------------------------------------------
|
| These routes are reserved for platform-level administrators.
| They are completely separate from the organization dashboard.
|
*/

Route::prefix('platform')
    ->middleware([
        'auth',
        'active.user',
        'platform.role:super-admin,billing,support,platform-auditor',
    ])
    ->name('platform.')
    ->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Activity Logs
        |--------------------------------------------------------------------------
        */
        Route::get(
            '/activity-logs',
            [PlatformActivityLogController::class, 'index']
        )->name('activity-logs.index');
        Route::get(
            '/activity-logs/{activityLog}',
            [PlatformActivityLogController::class, 'show']
        )->name('activity-logs.show');
                /*
        |--------------------------------------------------------------------------
        | Platform Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/', [
            PlatformDashboardController::class,
            'index',
        ])->name('dashboard');

        Route::get(
            '/staff',
            [
                PlatformStaffController::class,
                'index',
            ]
        )->name(
            'staff.index'
        );

        Route::get(
            '/staff/create',
            [
                PlatformStaffController::class,
                'create',
            ]
        )->name(
            'staff.create'
        );

        Route::post(
            '/staff',
            [
                PlatformStaffController::class,
                'store',
            ]
        )->name(
            'staff.store'
        );

        Route::get(
            '/staff/{platformStaff}/edit',
            [
                PlatformStaffController::class,
                'edit',
            ]
        )->name(
            'staff.edit'
        );

        Route::patch(
            '/staff/{platformStaff}',
            [
                PlatformStaffController::class,
                'update',
            ]
        )->name(
            'staff.update'
        );

        Route::patch(
            '/staff/{platformStaff}/status',
            [
                PlatformStaffController::class,
                'updateStatus',
            ]
        )->name(
            'staff.status'
        );


        Route::get('/billing', [
            PlatformBillingController::class,
            'index',
        ])->name('billing.index');


        Route::get('/subscription-plans', [
            SubscriptionPlanController::class,
            'index',
        ])->name('subscription-plans.index');

        Route::patch(
            '/subscription-plans/{subscriptionPlan}',
            [
                SubscriptionPlanController::class,
                'update',
            ]
        )->name('subscription-plans.update');

        Route::get(
            '/subscription-invoice-reminder-settings',
            [
                SubscriptionInvoiceReminderSettingsController::class,
                'index',
            ]
        )->name(
            'subscription-invoice-reminder-settings.index'
        );

        Route::patch(
            '/subscription-invoice-reminder-settings',
            [
                SubscriptionInvoiceReminderSettingsController::class,
                'update',
            ]
        )->name(
            'subscription-invoice-reminder-settings.update'
        );

        Route::get(
            '/subscription-payment-settings',
            [
                SubscriptionPaymentSettingsController::class,
                'index',
            ]
        )->name(
            'subscription-payment-settings.index'
        );

        Route::patch(
            '/subscription-payment-settings',
            [
                SubscriptionPaymentSettingsController::class,
                'update',
            ]
        )->name(
            'subscription-payment-settings.update'
        );

        Route::get(
            '/branding-settings',
            [
                PlatformBrandingSettingsController::class,
                'index',
            ]
        )->name(
            'branding-settings.index'
        );

        Route::patch(
            '/branding-settings',
            [
                PlatformBrandingSettingsController::class,
                'update',
            ]
        )->name(
            'branding-settings.update'
        );

        /*
        |--------------------------------------------------------------------------
        | Organization Management
        |--------------------------------------------------------------------------
        */

        Route::get('/organizations', [
            OrganizationController::class,
            'index',
        ])->name('organizations.index');

        Route::get('/organizations/{organization}', [
            OrganizationController::class,
            'show',
        ])->name('organizations.show');

        Route::get('/organizations/{organization}/edit', [
            OrganizationController::class,
            'edit',
        ])->name('organizations.edit');

        Route::put('/organizations/{organization}', [
            OrganizationController::class,
            'update',
        ])->name('organizations.update');

        Route::post(
            '/organizations/{organization}/subscription',
            [
                OrganizationController::class,
                'storeSubscription',
            ]
        )->name('organizations.subscription.store');

        Route::patch(
            '/organizations/{organization}/subscription-plan',
            [
                OrganizationController::class,
                'updateSubscriptionPlan',
            ]
        )->name('organizations.subscription-plan.update');

        Route::patch(
            '/organizations/{organization}/subscription-renewal',
            [
                OrganizationController::class,
                'updateSubscriptionRenewal',
            ]
        )->name('organizations.subscription-renewal.update');

        Route::patch(
            '/organizations/{organization}/subscription-suspension',
            [
                OrganizationController::class,
                'suspendSubscription',
            ]
        )->name('organizations.subscription-suspension.suspend');

        Route::delete(
            '/organizations/{organization}/subscription-suspension',
            [
                OrganizationController::class,
                'resumeSubscription',
            ]
        )->name('organizations.subscription-suspension.resume');

        Route::patch(
            '/organizations/{organization}/subscription-cancellation',
            [
                OrganizationController::class,
                'cancelSubscription',
            ]
        )->name('organizations.subscription-cancellation.update');

        Route::get(
            '/organizations/{organization}/subscription-invoices',
            [
                SubscriptionInvoiceController::class,
                'index',
            ]
        )->name('organizations.subscription-invoices.index');

        Route::post(
            '/organizations/{organization}/subscription-invoices',
            [
                SubscriptionInvoiceController::class,
                'store',
            ]
        )->name('organizations.subscription-invoices.store');

        Route::get(
            '/organizations/{organization}/subscription-invoices/{subscriptionInvoice}',
            [
                SubscriptionInvoiceController::class,
                'show',
            ]
        )->name('organizations.subscription-invoices.show');

        Route::patch(
            '/organizations/{organization}/subscription-invoices/{subscriptionInvoice}/issue',
            [
                SubscriptionInvoiceController::class,
                'issue',
            ]
        )->name('organizations.subscription-invoices.issue');

        Route::post(
            '/organizations/{organization}/subscription-invoices/{subscriptionInvoice}/payment',
            [
                SubscriptionTransactionController::class,
                'recordInvoicePayment',
            ]
        )->name(
            'organizations.subscription-invoices.payment.store'
        );

        Route::post(
            '/organizations/{organization}/subscription-invoices/{subscriptionInvoice}/reminder-notifications/{subscriptionInvoiceNotification}/retry',
            [
                SubscriptionInvoiceController::class,
                'retryReminder',
            ]
        )->name(
            'organizations.subscription-invoices.reminder-notifications.retry'
        );

        Route::get(
            '/organizations/{organization}/subscription-transactions',
            [
                SubscriptionTransactionController::class,
                'index',
            ]
        )->name('organizations.subscription-transactions.index');

        Route::post(
            '/organizations/{organization}/subscription-transactions',
            [
                SubscriptionTransactionController::class,
                'store',
            ]
        )->name('organizations.subscription-transactions.store');

        Route::get(
            '/organizations/{organization}/subscription-transactions/{subscriptionTransaction}',
            [
                SubscriptionTransactionController::class,
                'show',
            ]
        )->name('organizations.subscription-transactions.show');

        Route::patch(
            '/organizations/{organization}/subscription-bypass',
            [
                OrganizationController::class,
                'approveSubscriptionBypass',
            ]
        )->name('organizations.subscription-bypass.approve');

        Route::delete(
            '/organizations/{organization}/subscription-bypass',
            [
                OrganizationController::class,
                'revokeSubscriptionBypass',
            ]
        )->name('organizations.subscription-bypass.revoke');

            /*
        |--------------------------------------------------------------------------
        | Organization User Management
        |--------------------------------------------------------------------------
        |
        | Platform administrators can view, create, edit, enable, disable,
        | archive, and restore users belonging to an organization.
        |
        */

        Route::get(
            '/organizations/{organization}/users',
            [PlatformOrganizationUserController::class, 'index']
        )->name('organizations.users.index');

        Route::get(
            '/organizations/{organization}/users/archived',
            [PlatformOrganizationUserController::class, 'archived']
        )->name('organizations.users.archived');

        Route::get(
            '/organizations/{organization}/users/create',
            [PlatformOrganizationUserController::class, 'create']
        )->name('organizations.users.create');

        Route::post(
            '/organizations/{organization}/users',
            [PlatformOrganizationUserController::class, 'store']
        )->name('organizations.users.store');

        Route::get(
            '/organizations/{organization}/users/{user}/edit',
            [PlatformOrganizationUserController::class, 'edit']
        )->name('organizations.users.edit');

        Route::put(
            '/organizations/{organization}/users/{user}',
            [PlatformOrganizationUserController::class, 'update']
        )->name('organizations.users.update');

        Route::patch(
            '/organizations/{organization}/users/{user}/status',
            [PlatformOrganizationUserController::class, 'updateStatus']
        )->name('organizations.users.status');

        Route::delete(
            '/organizations/{organization}/users/{user}',
            [PlatformOrganizationUserController::class, 'destroy']
        )->name('organizations.users.destroy');

        Route::patch(
            '/organizations/{organization}/users/{user}/restore',
            [PlatformOrganizationUserController::class, 'restore']
        )
            ->withTrashed()
            ->name('organizations.users.restore');

        /*
        |--------------------------------------------------------------------------
        | Platform Security and Email Diagnostics
        |--------------------------------------------------------------------------
        */

        // PLATFORM_ADMIN_TOOLS_RELOCATION_ROUTES
        Route::get('/security/status', [
            SecurityStatusController::class,
            'index',
        ])->name('security.status');

        Route::get('/email-diagnostics', [
            EmailDiagnosticsController::class,
            'index',
        ])->name('email-diagnostics.index');

        Route::post('/email-diagnostics/test', [
            EmailDiagnosticsController::class,
            'sendTest',
        ])
            ->middleware('throttle:6,1')
            ->name('email-diagnostics.test');


        /*
        |--------------------------------------------------------------------------
        | Production Readiness
        |--------------------------------------------------------------------------
        */

        // PRODUCTION_READINESS_PLATFORM_ROUTES
        Route::get('/production-readiness', [
            ProductionReadinessController::class,
            'index',
        ])->name('production-readiness.index');

        Route::post('/production-readiness/backup', [
            ProductionReadinessController::class,
            'createBackup',
        ])
            ->middleware('throttle:3,1')
            ->name('production-readiness.backup');

        Route::post('/production-readiness/prune', [
            ProductionReadinessController::class,
            'prune',
        ])
            ->middleware('throttle:3,1')
            ->name('production-readiness.prune');

        Route::post('/production-readiness/optimize', [
            ProductionReadinessController::class,
            'optimize',
        ])
            ->middleware('throttle:3,1')
            ->name('production-readiness.optimize');

        Route::post('/production-readiness/queue-restart', [
            ProductionReadinessController::class,
            'restartQueue',
        ])
            ->middleware('throttle:3,1')
            ->name('production-readiness.queue-restart');

        Route::post('/production-readiness/maintenance/enable', [
            ProductionReadinessController::class,
            'enableMaintenance',
        ])
            ->middleware('throttle:2,1')
            ->name('production-readiness.maintenance.enable');

        Route::post('/production-readiness/maintenance/disable', [
            ProductionReadinessController::class,
            'disableMaintenance',
        ])
            ->middleware('throttle:2,1')
            ->name('production-readiness.maintenance.disable');

});


/*
|--------------------------------------------------------------------------
| Platform Route Role Policies
|--------------------------------------------------------------------------
|
| Only four Platform roles exist:
| Super Admin, Billing, Support, and Platform Auditor.
|
| The outer Platform group admits those four roles. Each route receives a
| narrower policy below according to its responsibility.
|
*/

$platformRouteRolePolicies = [
    /*
     * Shared overview.
     */
    'super-admin,billing,support,platform-auditor' => [
        'platform.dashboard',
        'platform.organizations.index',
        'platform.organizations.show',
    ],

    /*
     * Billing records may also be viewed by Platform Auditor.
     */
    'super-admin,billing,platform-auditor' => [
        'platform.billing.index',

        'platform.organizations.subscription-invoices.index',
        'platform.organizations.subscription-invoices.show',

        'platform.organizations.subscription-transactions.index',
        'platform.organizations.subscription-transactions.show',
    ],

    /*
     * Billing configuration and billing write operations.
     */
    'super-admin,billing' => [
        'platform.subscription-plans.index',
        'platform.subscription-plans.update',

        'platform.subscription-invoice-reminder-settings.index',
        'platform.subscription-invoice-reminder-settings.update',

        'platform.subscription-payment-settings.index',
        'platform.subscription-payment-settings.update',

        'platform.organizations.subscription.store',
        'platform.organizations.subscription-plan.update',
        'platform.organizations.subscription-renewal.update',
        'platform.organizations.subscription-suspension.suspend',
        'platform.organizations.subscription-suspension.resume',
        'platform.organizations.subscription-cancellation.update',

        'platform.organizations.subscription-invoices.store',
        'platform.organizations.subscription-invoices.issue',
        'platform.organizations.subscription-invoices.payment.store',
        'platform.organizations.subscription-invoices.reminder-notifications.retry',

        'platform.organizations.subscription-transactions.store',
    ],

    /*
     * Organization-user records for Support and Platform Auditor.
     */
    'super-admin,support,platform-auditor' => [
        'platform.organizations.users.index',
        'platform.organizations.users.archived',
    ],

    /*
     * Read-only audit and security access.
     */
    'super-admin,platform-auditor' => [
        'platform.activity-logs.index',
        'platform.activity-logs.show',
        'platform.security.status',
    ],

    /*
     * Sensitive administration remains Super Admin-only.
     */
    'super-admin' => [
        'platform.branding-settings.index',
        'platform.branding-settings.update',

        'platform.staff.index',
        'platform.staff.create',
        'platform.staff.store',
        'platform.staff.edit',
        'platform.staff.update',
        'platform.staff.status',

        'platform.organizations.edit',
        'platform.organizations.update',

        'platform.organizations.subscription-bypass.approve',
        'platform.organizations.subscription-bypass.revoke',

        'platform.organizations.users.create',
        'platform.organizations.users.store',
        'platform.organizations.users.edit',
        'platform.organizations.users.update',
        'platform.organizations.users.status',
        'platform.organizations.users.destroy',
        'platform.organizations.users.restore',

        'platform.email-diagnostics.index',
        'platform.email-diagnostics.test',

        'platform.production-readiness.index',
        'platform.production-readiness.backup',
        'platform.production-readiness.prune',
        'platform.production-readiness.optimize',
        'platform.production-readiness.queue-restart',
        'platform.production-readiness.maintenance.enable',
        'platform.production-readiness.maintenance.disable',
    ],
];

$platformRoutes =
    Route::getRoutes();

/*
 * Routes receive their names after registration. Refreshing this lookup is
 * required before getByName() can reliably find the prefixed route names.
 */
$platformRoutes->refreshNameLookups();

$platformPolicyRouteNames = [];

foreach (
    $platformRouteRolePolicies
    as $allowedRoles => $routeNames
) {
    foreach ($routeNames as $routeName) {
        if (
            in_array(
                $routeName,
                $platformPolicyRouteNames,
                true
            )
        ) {
            throw new LogicException(
                "Duplicate Platform route policy: {$routeName}"
            );
        }

        $platformRoute =
            $platformRoutes->getByName(
                $routeName
            );

        if ($platformRoute === null) {
            throw new LogicException(
                'Platform route policy references an unknown route: '
                .$routeName
            );
        }

        $platformRoute->middleware(
            "platform.role:{$allowedRoles}"
        );

        $platformPolicyRouteNames[] =
            $routeName;
    }
}

$registeredPlatformRouteNames = [];

foreach (
    $platformRoutes
    as $registeredRoute
) {
    $registeredRouteName =
        $registeredRoute->getName();

    if (
        is_string(
            $registeredRouteName
        )
        && str_starts_with(
            $registeredRouteName,
            'platform.'
        )
    ) {
        $registeredPlatformRouteNames[] =
            $registeredRouteName;
    }
}

$unprotectedPlatformRoutes =
    array_values(
        array_diff(
            $registeredPlatformRouteNames,
            $platformPolicyRouteNames
        )
    );

if ($unprotectedPlatformRoutes !== []) {
    throw new LogicException(
        'Platform routes without a role policy: '
        .implode(
            ', ',
            $unprotectedPlatformRoutes
        )
    );
}

unset(
    $allowedRoles,
    $platformPolicyRouteNames,
    $platformRoute,
    $platformRouteRolePolicies,
    $platformRoutes,
    $registeredPlatformRouteNames,
    $registeredRoute,
    $registeredRouteName,
    $routeName,
    $routeNames,
    $unprotectedPlatformRoutes,
);

/*
|--------------------------------------------------------------------------
| Application Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
