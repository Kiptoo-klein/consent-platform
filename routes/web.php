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
use App\Http\Controllers\OrganizationSubscriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicConsentSigningController;
use App\Http\Controllers\PublicSigningStationController;
use App\Http\Controllers\SigningStationController;
use App\Http\Controllers\Platform\ProductionReadinessController;
use App\Http\Controllers\EmailDiagnosticsController;
use App\Http\Controllers\SecurityStatusController;
use App\Http\Controllers\Platform\PlatformDashboardController;
use App\Http\Controllers\Platform\OrganizationController;
use App\Http\Controllers\Platform\PlatformOrganizationUserController;
use App\Http\Controllers\Platform\PlatformActivityLogController;
use App\Http\Controllers\ConsentPdfController;
use App\Http\Controllers\ConsentAuditController;
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
        'throttle:public-station-view',
    ])
    ->name('public-signing-stations.show');

Route::get('/station/{stationToken}/review', [
    PublicSigningStationController::class,
    'review',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'throttle:public-station-view',
    ])
    ->name('public-signing-stations.review');

Route::post('/station/{stationToken}/continue', [
    PublicSigningStationController::class,
    'confirmReview',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'throttle:public-station-action',
    ])
    ->name('public-signing-stations.continue');

Route::get('/station/{stationToken}/details', [
    PublicSigningStationController::class,
    'details',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'throttle:public-station-view',
    ])
    ->name('public-signing-stations.details');

Route::post('/station/{stationToken}/start', [
    PublicSigningStationController::class,
    'start',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
        'throttle:public-station-action',
    ])
    ->name('public-signing-stations.start');

Route::post('/station/{stationToken}/cancel', [
    PublicSigningStationController::class,
    'cancel',
])->middleware([
        \App\Http\Middleware\ValidateStationToken::class,
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
        'throttle:public-consent-view',
    ])
    ->name('public-consent.show');

Route::patch('/sign/{accessToken}', [
    PublicConsentSigningController::class,
    'update',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
        'throttle:public-consent-write',
    ])
    ->name('public-consent.update');

Route::get('/sign/{accessToken}/signature', [
    PublicConsentSigningController::class,
    'signature',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
        'throttle:public-consent-view',
    ])
    ->name('public-consent.signature');

Route::post('/sign/{accessToken}/complete', [
    PublicConsentSigningController::class,
    'complete',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
        'throttle:public-signature-submit',
        \App\Http\Middleware\HardenSignatureSubmission::class,
    ])
    ->name('public-consent.complete');

Route::post('/sign/{accessToken}/cancel', [
    PublicConsentSigningController::class,
    'cancel',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
        'throttle:public-consent-write',
    ])
    ->name('public-consent.cancel');

Route::get('/sign/{accessToken}/completed', [
    PublicConsentSigningController::class,
    'completed',
])->middleware([
        \App\Http\Middleware\ValidateConsentToken::class,
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
    ])->name('organization-branding.edit');

    Route::put('/organization/branding', [
        OrganizationBrandingController::class,
        'update',
    ])->name('organization-branding.update');

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
        'platform.role:super-admin',
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
| Laravel Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
