<?php

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\User;
use App\Services\SubscriptionUsageLimitService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function evaluationOrganizationContext(): array
{
    test()->seed(SubscriptionPlanSeeder::class);

    test()->post('/register', [
        'organization_name' =>
            'Evaluation Capacity Clinic',
        'name' =>
            'Evaluation Administrator',
        'email' =>
            'evaluation-capacity@example.com',
        'password' =>
            'StrongPass1!',
        'password_confirmation' =>
            'StrongPass1!',
    ]);

    $organization = Organization::query()
        ->where(
            'name',
            'Evaluation Capacity Clinic'
        )
        ->firstOrFail();

    $administrator = User::query()
        ->where(
            'email',
            'evaluation-capacity@example.com'
        )
        ->firstOrFail();

    $subscription = $organization
        ->subscription()
        ->with('plan')
        ->firstOrFail();

    expect($subscription->status)->toBe(
        OrganizationSubscriptionStatus::EVALUATION
    );

    expect($subscription->payment_status)->toBe(
        SubscriptionPaymentStatus::UNPAID
    );

    return [
        $organization,
        $administrator,
        $subscription,
    ];
}

test('evaluation limits are independent from basic plan limits', function () {
    [
        $organization,
        $administrator,
        $subscription,
    ] = evaluationOrganizationContext();

    $subscription->plan->update([
        'max_users' => 99,
        'max_consent_managers' => 99,
        'max_staff' => 99,
        'max_auditors' => 99,
        'max_active_kiosks' => 99,
        'max_consent_templates' => 99,
        'max_signed_consents_per_period' => 99,
    ]);

    $subscription = $subscription
        ->fresh()
        ->load('plan');

    expect($subscription->effectiveUserLimit())
        ->toBe(2);

    expect(
        $subscription->effectiveRoleLimit(
            'Consent Manager'
        )
    )->toBe(1);

    expect(
        $subscription->effectiveRoleLimit('Staff')
    )->toBe(1);

    expect(
        $subscription->effectiveRoleLimit('Auditor')
    )->toBe(1);

    expect($subscription->effectiveActiveKioskLimit())
        ->toBe(1);

    expect($subscription->effectiveConsentTemplateLimit())
        ->toBe(5);

    expect($subscription->effectiveSignedConsentLimit())
        ->toBe(5);
});

test('evaluation allows five non archived templates and blocks the sixth', function () {
    [
        $organization,
        $administrator,
    ] = evaluationOrganizationContext();

    for ($i = 1; $i <= 5; $i++) {
        ConsentTemplate::query()->create([
            'organization_id' =>
                $organization->id,
            'title' =>
                "Evaluation Template {$i}",
            'usage_type' =>
                ConsentTemplate::USAGE_INDIVIDUAL,
            'template_schema' => [],
            'status' => 'draft',
        ]);
    }

    expect(
        app(
            SubscriptionUsageLimitService::class
        )->templateUsage($organization->id)
    )->toBe(5);

    $this->expectException(
        ValidationException::class
    );

    DB::transaction(function () use (
        $organization
    ): void {
        app(
            SubscriptionUsageLimitService::class
        )->assertTemplateSlotAvailableLocked(
            $organization->id
        );
    });
});

test('evaluation completed consent limit is lifetime rather than period based', function () {
    [
        $organization,
        $administrator,
        $subscription,
    ] = evaluationOrganizationContext();

    $template = ConsentTemplate::query()->create([
        'organization_id' =>
            $organization->id,
        'title' =>
            'Evaluation Lifetime Consent',
        'usage_type' =>
            ConsentTemplate::USAGE_INDIVIDUAL,
        'template_schema' => [],
        'active_version_id' => null,
        'has_unpublished_changes' => false,
        'status' => 'draft',
    ]);

    $version = $template
        ->versions()
        ->create([
            'version_number' => 1,
            'title' => $template->title,
            'description' => $template->description,
            'template_schema' =>
                $template->template_schema,
            'published_at' => now(),
            'published_by' =>
                $administrator->id,
        ]);

    $template->update([
        'active_version_id' =>
            $version->id,
        'has_unpublished_changes' => false,
        'status' => 'published',
    ]);

    /*
     * Deliberately move subscription dates forward. Evaluation usage
     * must still count older completed records.
     */
    $subscription->update([
        'starts_at' => now()->addYear(),
        'current_period_starts_at' =>
            now()->addYear(),
        'current_period_ends_at' =>
            now()->addYear()->addMonth(),
    ]);

    for ($i = 1; $i <= 5; $i++) {
        ConsentSession::query()->create([
            'organization_id' =>
                $organization->id,
            'consent_template_id' =>
                $template->id,
            'consent_template_version_id' =>
                $version->id,
            'signing_station_id' => null,
            'created_by' =>
                $administrator->id,
            'signer_name' =>
                "Evaluation Signer {$i}",
            'signer_email' => null,
            'signer_reference' => null,
            'access_token' =>
                (string) Str::uuid(),
            'status' =>
                ConsentSession::STATUS_COMPLETED,
            'responses' => [],
            'started_at' =>
                now()->subYear(),
            'completed_at' =>
                now()->subYear(),
        ]);
    }

    $service = app(
        SubscriptionUsageLimitService::class
    );

    expect(
        $service->signedConsentUsage(
            $organization->id
        )
    )->toBe(5);

    $capacity =
        $service->signedConsentCapacity(
            $organization->id
        );

    expect($capacity['plan_name'])
        ->toBe('Free evaluation');

    expect($capacity['limit'])
        ->toBe(5);

    expect($capacity['used'])
        ->toBe(5);

    expect($capacity['remaining'])
        ->toBe(0);

    expect($capacity['reached'])
        ->toBeTrue();

    expect($capacity['period_start'])
        ->toBeNull();

    expect($capacity['period_end'])
        ->toBeNull();

    $this->expectException(
        ValidationException::class
    );

    DB::transaction(function () use (
        $organization,
        $service
    ): void {
        $service
            ->assertSignedConsentSlotAvailableLocked(
                $organization->id
            );
    });
});

test('evaluation kiosk capacity stays one when basic plan changes', function () {
    [
        $organization,
        $administrator,
        $subscription,
    ] = evaluationOrganizationContext();

    $subscription->plan->update([
        'max_active_kiosks' => 20,
    ]);

    $template = ConsentTemplate::query()->create([
        'organization_id' =>
            $organization->id,
        'title' =>
            'Evaluation Kiosk Template',
        'usage_type' =>
            ConsentTemplate::USAGE_SIGNING_STATION,
        'template_schema' => [],
        'status' => 'draft',
    ]);

    SigningStation::query()->create([
        'organization_id' =>
            $organization->id,
        'consent_template_id' =>
            $template->id,
        'created_by' =>
            $administrator->id,
        'name' =>
            'Evaluation Kiosk',
        'station_token' =>
            (string) Str::uuid(),
        'active' => true,
        'require_email' => false,
        'require_reference' => false,
    ]);

    $capacity = app(
        SubscriptionUsageLimitService::class
    )->activeKioskCapacity(
        $organization->id
    );

    expect($capacity['plan_name'])
        ->toBe('Free evaluation');

    expect($capacity['limit'])->toBe(1);
    expect($capacity['used'])->toBe(1);
    expect($capacity['remaining'])->toBe(0);
    expect($capacity['reached'])->toBeTrue();
});
