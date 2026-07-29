<?php

use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);
test('organization users can view their subscription status', function () {
    $this->seed(SubscriptionPlanSeeder::class);

    $this->post('/register', [
        'organization_name' => 'Subscription Test Clinic',
        'name' => 'Subscription Administrator',
        'email' => 'subscription-admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $response = $this->get('/organization/subscription');

    $response->assertOk();

    $response->assertSee('Subscription Status');
    $response->assertSee('Basic');
    $response->assertSee('Unpaid');
    $response->assertSee('Payment required');
});

test(
    'subscription page shows current organization usage against plan limits',
    function () {
        $this->seed(SubscriptionPlanSeeder::class);

        $this->post('/register', [
            'organization_name' => 'Usage Dashboard Clinic',
            'name' => 'Usage Administrator',
            'email' => 'usage-admin@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ]);

        $this->assertAuthenticated();

        $organization = Organization::query()
            ->where('name', 'Usage Dashboard Clinic')
            ->firstOrFail();

        $subscription = $organization
            ->subscription()
            ->with('plan')
            ->firstOrFail();

        $subscription->plan->update([
            'max_users' => 10,
            'max_consent_managers' => 2,
            'max_staff' => 5,
            'max_auditors' => 3,
            'max_active_kiosks' => 5,
        ]);

        $administrator = User::query()
            ->where('email', 'usage-admin@example.com')
            ->firstOrFail();

        $consentManagerRole = Role::query()->firstOrCreate([
            'organization_id' => $organization->id,
            'guard_name' => 'web',
            'name' => 'Consent Manager',
        ]);

        $staffRole = Role::query()->firstOrCreate([
            'organization_id' => $organization->id,
            'guard_name' => 'web',
            'name' => 'Staff',
        ]);

        $auditorRole = Role::query()->firstOrCreate([
            'organization_id' => $organization->id,
            'guard_name' => 'web',
            'name' => 'Auditor',
        ]);

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($organization->id);

        $consentManager = User::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => false,
        ]);

        $consentManager->assignRole($consentManagerRole);

        $staff = User::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        $staff->assignRole($staffRole);

        $auditor = User::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        $auditor->assignRole($auditorRole);

        $archivedStaff = User::factory()->create([
            'organization_id' => $organization->id,
            'is_active' => false,
        ]);

        $archivedStaff->assignRole($staffRole);
        $archivedStaff->delete();

        $template = ConsentTemplate::create([
            'organization_id' => $organization->id,
            'title' => 'Kiosk Usage Template',
            'usage_type' =>
                ConsentTemplate::USAGE_SIGNING_STATION,
            'template_schema' => [],
            'status' => 'draft',
        ]);

        SigningStation::create([
            'organization_id' => $organization->id,
            'consent_template_id' => $template->id,
            'created_by' => $administrator->id,
            'name' => 'Active Usage Kiosk',
            'station_token' => 'active-usage-kiosk',
            'active' => true,
            'require_email' => false,
            'require_reference' => false,
        ]);

        SigningStation::create([
            'organization_id' => $organization->id,
            'consent_template_id' => $template->id,
            'created_by' => $administrator->id,
            'name' => 'Inactive Usage Kiosk',
            'station_token' => 'inactive-usage-kiosk',
            'active' => false,
            'require_email' => false,
            'require_reference' => false,
        ]);

        $response = $this->get(
            route('organization-subscription.show')
        );

        $response->assertOk();

        $response->assertViewHas('usage', [
            'users' => 4,
            'consent_managers' => 1,
            'staff' => 1,
            'auditors' => 1,
            'active_kiosks' => 1,
        ]);

        $response->assertSeeTextInOrder([
            'Total users',
            '4 of 10',
            'Consent Managers',
            '1 of 2',
            'Staff',
            '1 of 5',
            'Auditors',
            '1 of 3',
            'Active kiosks',
            '1 of 5',
        ]);
    }
);
