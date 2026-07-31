<?php

namespace Tests\Feature;

use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\SigningStationDeviceLease;
use App\Models\User;
use App\Services\SigningStationDeviceLeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class SigningStationDeviceLeaseTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private User $administrator;
    private ConsentTemplate $template;
    private SigningStation $station;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization =
            Organization::query()->create([
                'name' =>
                    'Device Lease Clinic',

                'slug' =>
                    'device-lease-clinic',
            ]);

        $this->administrator =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'is_active' =>
                    true,
            ]);

        $this->enablePaidOrganizationAccess(
            $this->organization,
            $this->administrator
        );

        $subscription =
            $this->organization
                ->subscription()
                ->with('plan')
                ->firstOrFail();

        $subscription->plan->update([
            'max_active_kiosks' =>
                1,
        ]);

        $this->template =
            $this->createPublishedTemplate();

        $this->station =
            SigningStation::query()->create([
                'organization_id' =>
                    $this->organization->id,

                'consent_template_id' =>
                    $this->template->id,

                'created_by' =>
                    $this->administrator->id,

                'name' =>
                    'Reception Tablet',

                'station_token' =>
                    Str::random(64),

                'active' =>
                    true,

                'require_email' =>
                    false,

                'require_reference' =>
                    false,

                'auto_reset_seconds' =>
                    3,
            ]);
    }

    public function test_first_browser_acquires_a_kiosk_device_lease(): void
    {
        $this
            ->withSession([
                SigningStationDeviceLeaseService::
                    SESSION_KEY =>
                        str_repeat('A', 64),
            ])
            ->get(
                route(
                    'public-signing-stations.show',
                    $this->station->station_token
                )
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'signing_station_device_leases',
            1
        );

        $this->assertDatabaseHas(
            'signing_station_device_leases',
            [
                'organization_id' =>
                    $this->organization->id,

                'signing_station_id' =>
                    $this->station->id,

                'device_key' =>
                    hash(
                        'sha256',
                        str_repeat('A', 64)
                    ),
            ]
        );
    }

    public function test_same_browser_reuses_its_existing_slot(): void
    {
        foreach (range(1, 2) as $requestNumber) {
            $this
                ->withSession([
                    SigningStationDeviceLeaseService::
                        SESSION_KEY =>
                            str_repeat('B', 64),
                ])
                ->get(
                    route(
                        'public-signing-stations.show',
                        $this->station->station_token
                    )
                )
                ->assertOk();
        }

        $this->assertDatabaseCount(
            'signing_station_device_leases',
            1
        );
    }

    public function test_second_browser_is_blocked_when_device_limit_is_reached(): void
    {
        $this
            ->withSession([
                SigningStationDeviceLeaseService::
                    SESSION_KEY =>
                        str_repeat('C', 64),
            ])
            ->get(
                route(
                    'public-signing-stations.show',
                    $this->station->station_token
                )
            )
            ->assertOk();

        $this
            ->withSession([
                SigningStationDeviceLeaseService::
                    SESSION_KEY =>
                        str_repeat('D', 64),
            ])
            ->get(
                route(
                    'public-signing-stations.show',
                    $this->station->station_token
                )
            )
            ->assertStatus(429)
            ->assertSeeText(
                'No kiosk device slot is available'
            );

        $this->assertDatabaseCount(
            'signing_station_device_leases',
            1
        );
    }

    public function test_expired_device_lease_releases_the_slot(): void
    {
        $this
            ->withSession([
                SigningStationDeviceLeaseService::
                    SESSION_KEY =>
                        str_repeat('E', 64),
            ])
            ->get(
                route(
                    'public-signing-stations.show',
                    $this->station->station_token
                )
            )
            ->assertOk();

        SigningStationDeviceLease::query()
            ->update([
                'expires_at' =>
                    now()->subMinute(),
            ]);

        $this
            ->withSession([
                SigningStationDeviceLeaseService::
                    SESSION_KEY =>
                        str_repeat('F', 64),
            ])
            ->get(
                route(
                    'public-signing-stations.show',
                    $this->station->station_token
                )
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'signing_station_device_leases',
            1
        );

        $this->assertDatabaseHas(
            'signing_station_device_leases',
            [
                'device_key' =>
                    hash(
                        'sha256',
                        str_repeat('F', 64)
                    ),
            ]
        );
    }

    public function test_heartbeat_extends_the_current_device_lease(): void
    {
        $deviceToken =
            str_repeat('G', 64);

        $this
            ->withSession([
                SigningStationDeviceLeaseService::
                    SESSION_KEY =>
                        $deviceToken,
            ])
            ->get(
                route(
                    'public-signing-stations.show',
                    $this->station->station_token
                )
            )
            ->assertOk();

        $lease =
            SigningStationDeviceLease::query()
                ->sole();

        $lease->update([
            'expires_at' =>
                now()->addSecond(),
        ]);

        $previousExpiry =
            $lease
                ->fresh()
                ->expires_at;

        $this
            ->withSession([
                SigningStationDeviceLeaseService::
                    SESSION_KEY =>
                        $deviceToken,
            ])
            ->postJson(
                route(
                    'public-signing-stations.heartbeat',
                    $this->station->station_token
                )
            )
            ->assertOk()
            ->assertJson([
                'active' =>
                    true,
            ]);

        $this->assertTrue(
            $lease
                ->fresh()
                ->expires_at
                ->greaterThan(
                    $previousExpiry
                )
        );
    }

    public function test_pausing_or_regenerating_station_releases_device_leases(): void
    {
        $this
            ->withSession([
                SigningStationDeviceLeaseService::
                    SESSION_KEY =>
                        str_repeat('H', 64),
            ])
            ->get(
                route(
                    'public-signing-stations.show',
                    $this->station->station_token
                )
            )
            ->assertOk();

        $this->assertDatabaseCount(
            'signing_station_device_leases',
            1
        );

        $this
            ->actingAs(
                $this->administrator
            )
            ->patch(
                route(
                    'signing-stations.toggle',
                    $this->station
                )
            )
            ->assertSessionHas(
                'success',
                'Signing station paused.'
            );

        $this->assertDatabaseCount(
            'signing_station_device_leases',
            0
        );

        $this
            ->patch(
                route(
                    'signing-stations.toggle',
                    $this->station
                )
            )
            ->assertSessionHas(
                'success',
                'Signing station activated.'
            );

        $this
            ->withSession([
                SigningStationDeviceLeaseService::
                    SESSION_KEY =>
                        str_repeat('H', 64),
            ])
            ->get(
                route(
                    'public-signing-stations.show',
                    $this->station->fresh()->station_token
                )
            )
            ->assertOk();

        $this
            ->post(
                route(
                    'signing-stations.regenerate-token',
                    $this->station
                )
            )
            ->assertSessionHas(
                'success'
            );

        $this->assertDatabaseCount(
            'signing_station_device_leases',
            0
        );
    }

    public function test_station_and_station_consent_routes_use_device_lease_middleware(): void
    {
        foreach ([
            'public-signing-stations.show',
            'public-signing-stations.review',
            'public-signing-stations.continue',
            'public-signing-stations.details',
            'public-signing-stations.start',
            'public-signing-stations.cancel',
            'public-signing-stations.heartbeat',
            'public-consent.show',
            'public-consent.signature',
            'public-consent.complete',
            'public-consent.cancel',
            'public-consent.completed',
        ] as $routeName) {
            $route =
                Route::getRoutes()
                    ->getByName(
                        $routeName
                    );

            $this->assertNotNull(
                $route,
                "Route {$routeName} was not found."
            );

            $this->assertContains(
                'kiosk.device',
                $route->gatherMiddleware(),
                "Route {$routeName} is missing kiosk.device middleware."
            );
        }
    }

    private function createPublishedTemplate(): ConsentTemplate
    {
        $template =
            ConsentTemplate::query()->create([
                'organization_id' =>
                    $this->organization->id,

                'title' =>
                    'Device Lease Consent',

                'description' =>
                    'Consent used by device lease tests.',

                'category' =>
                    'Testing',

                'usage_type' =>
                    ConsentTemplate::
                        USAGE_SIGNING_STATION,

                'template_schema' =>
                    [
                        'sections' =>
                            [],
                    ],

                'active_version_id' =>
                    null,

                'has_unpublished_changes' =>
                    false,

                'status' =>
                    'draft',
            ]);

        $version =
            $template
                ->versions()
                ->create([
                    'version_number' =>
                        1,

                    'title' =>
                        $template->title,

                    'description' =>
                        $template->description,

                    'template_schema' =>
                        $template->template_schema,

                    'published_at' =>
                        now(),

                    'published_by' =>
                        $this->administrator->id,
                ]);

        $template->update([
            'active_version_id' =>
                $version->id,

            'has_unpublished_changes' =>
                false,

            'status' =>
                'published',
        ]);

        return $template->refresh();
    }
}
