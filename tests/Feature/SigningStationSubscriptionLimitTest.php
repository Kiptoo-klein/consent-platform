<?php

namespace Tests\Feature;

use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SigningStation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SigningStationSubscriptionLimitTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $administrator;

    private ConsentTemplate $template;

    private OrganizationSubscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Kiosk Limit Clinic',
            'slug' => 'kiosk-limit-clinic',
        ]);

        $this->administrator = User::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
        ]);

        $this->enablePaidOrganizationAccess(
            $this->organization,
            $this->administrator
        );

        $this->subscription = $this->organization
            ->subscription()
            ->with('plan')
            ->firstOrFail();

        $this->subscription->plan->update([
            'max_active_kiosks' => 1,
        ]);

        $this->template = $this->createPublishedTemplate(
            $this->organization,
            $this->administrator,
            'Kiosk Limit Consent'
        );

        $this->actingAs($this->administrator);
    }

    public function test_creating_an_active_kiosk_is_blocked_when_the_limit_is_reached(): void
    {
        $this->createStation(
            $this->organization,
            $this->template,
            $this->administrator,
            'Existing Active Kiosk',
            true
        );

        $response = $this->post(
            route('signing-stations.store'),
            [
                'name' => 'Blocked New Kiosk',
                'consent_template_id' => $this->template->id,
                'auto_reset_seconds' => 3,
            ]
        );

        $response->assertSessionHasErrors([
            'subscription' =>
                'The active signing station limit for this subscription plan has been reached.',
        ]);

        $this->assertDatabaseMissing('signing_stations', [
            'organization_id' => $this->organization->id,
            'name' => 'Blocked New Kiosk',
        ]);
    }

    public function test_activating_an_inactive_kiosk_is_blocked_when_the_limit_is_reached(): void
    {
        $this->createStation(
            $this->organization,
            $this->template,
            $this->administrator,
            'Existing Active Kiosk',
            true
        );

        $inactiveStation = $this->createStation(
            $this->organization,
            $this->template,
            $this->administrator,
            'Inactive Kiosk',
            false
        );

        $response = $this->patch(
            route(
                'signing-stations.toggle',
                $inactiveStation
            )
        );

        $response->assertSessionHasErrors([
            'subscription' =>
                'The active signing station limit for this subscription plan has been reached.',
        ]);

        $this->assertFalse(
            $inactiveStation->refresh()->active
        );
    }

    public function test_an_active_kiosk_can_always_be_paused(): void
    {
        $activeStation = $this->createStation(
            $this->organization,
            $this->template,
            $this->administrator,
            'Active Kiosk',
            true
        );

        $response = $this->patch(
            route(
                'signing-stations.toggle',
                $activeStation
            )
        );

        $response
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas(
                'success',
                'Signing station paused.'
            );

        $this->assertFalse(
            $activeStation->refresh()->active
        );
    }

    public function test_activation_succeeds_after_another_kiosk_is_paused(): void
    {
        $activeStation = $this->createStation(
            $this->organization,
            $this->template,
            $this->administrator,
            'First Kiosk',
            true
        );

        $inactiveStation = $this->createStation(
            $this->organization,
            $this->template,
            $this->administrator,
            'Second Kiosk',
            false
        );

        $this->patch(
            route(
                'signing-stations.toggle',
                $activeStation
            )
        )->assertSessionHas(
            'success',
            'Signing station paused.'
        );

        $response = $this->patch(
            route(
                'signing-stations.toggle',
                $inactiveStation
            )
        );

        $response
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas(
                'success',
                'Signing station activated.'
            );

        $this->assertFalse(
            $activeStation->refresh()->active
        );

        $this->assertTrue(
            $inactiveStation->refresh()->active
        );
    }

    public function test_active_kiosks_from_another_organization_do_not_count_toward_the_limit(): void
    {
        $otherOrganization = Organization::create([
            'name' => 'Other Kiosk Clinic',
            'slug' => 'other-kiosk-clinic',
        ]);

        $otherAdministrator = User::factory()->create([
            'organization_id' => $otherOrganization->id,
            'is_active' => true,
        ]);

        $otherTemplate = $this->createPublishedTemplate(
            $otherOrganization,
            $otherAdministrator,
            'Other Organization Consent'
        );

        $this->createStation(
            $otherOrganization,
            $otherTemplate,
            $otherAdministrator,
            'Other Organization Active Kiosk',
            true
        );

        $response = $this->post(
            route('signing-stations.store'),
            [
                'name' => 'Own Organization Kiosk',
                'consent_template_id' => $this->template->id,
                'auto_reset_seconds' => 3,
            ]
        );

        $createdStation = SigningStation::query()
            ->where(
                'organization_id',
                $this->organization->id
            )
            ->where('name', 'Own Organization Kiosk')
            ->firstOrFail();

        $response
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(
                route(
                    'signing-stations.show',
                    $createdStation
                )
            )
            ->assertSessionHas(
                'success',
                'Signing station created successfully.'
            );

        $this->assertTrue($createdStation->active);
    }

    public function test_legacy_individual_template_can_be_used_for_signing_station(): void
    {
        $this->template->update([
            'usage_type' =>
                ConsentTemplate::USAGE_INDIVIDUAL,
        ]);

        $response = $this->post(
            route(
                'signing-stations.store'
            ),
            [
                'name' =>
                    'Universal Consent Kiosk',

                'consent_template_id' =>
                    $this->template->id,

                'auto_reset_seconds' =>
                    3,
            ]
        );

        $station =
            SigningStation::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->where(
                    'name',
                    'Universal Consent Kiosk'
                )
                ->firstOrFail();

        $response
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(
                route(
                    'signing-stations.show',
                    $station
                )
            );

        $this->assertSame(
            $this->template->id,
            $station->consent_template_id
        );
    }

    private function createPublishedTemplate(
        Organization $organization,
        User $publisher,
        string $title
    ): ConsentTemplate {
        $template = ConsentTemplate::create([
            'organization_id' => $organization->id,
            'title' => $title,
            'description' => 'Subscription kiosk limit test.',
            'category' => 'Testing',
            'usage_type' =>
                ConsentTemplate::USAGE_SIGNING_STATION,
            'template_schema' => [
                'sections' => [],
            ],
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
                'template_schema' => $template->template_schema,
                'published_at' => now(),
                'published_by' => $publisher->id,
            ]);

        $template->update([
            'active_version_id' => $version->id,
            'has_unpublished_changes' => false,
            'status' => 'published',
        ]);

        return $template->refresh();
    }

    private function createStation(
        Organization $organization,
        ConsentTemplate $template,
        User $creator,
        string $name,
        bool $active
    ): SigningStation {
        return SigningStation::create([
            'organization_id' => $organization->id,
            'consent_template_id' => $template->id,
            'created_by' => $creator->id,
            'name' => $name,
            'station_token' => (string) Str::uuid(),
            'active' => $active,
            'require_email' => false,
            'require_reference' => false,
            'auto_reset_seconds' => 3,
        ]);
    }
}
