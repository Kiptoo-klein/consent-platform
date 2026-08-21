<?php

namespace Tests\Feature;

use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class KioskOfflineTemplateStateTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::query()->create([
            'name' => 'Kiosk Lifecycle Clinic',
            'slug' => 'kiosk-lifecycle-clinic',
        ]);

        $this->administrator = User::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
        ]);

        $this->enablePaidOrganizationAccess(
            $this->organization,
            $this->administrator
        );

        $this->actingAs(
            $this->administrator
        );
    }

    public function test_unpublishing_template_pauses_active_kiosks_releases_leases_and_warns_user(): void
    {
        $template = $this->createPublishedTemplate();

        $firstStation = $this->createStation(
            $template,
            'Reception Kiosk',
            true
        );

        $secondStation = $this->createStation(
            $template,
            'Lobby Kiosk',
            true
        );

        $alreadyPausedStation = $this->createStation(
            $template,
            'Backup Kiosk',
            false
        );

        $this->createDeviceLease(
            $firstStation
        );

        $this->createDeviceLease(
            $secondStation
        );

        $response = $this->post(
            route(
                'consent-templates.unpublish',
                $template
            )
        );

        $response
            ->assertRedirect(
                route('consent-templates.manage')
            )
            ->assertSessionHas(
                'success',
                'The consent template is now offline. '
                .'2 signing stations using this template '
                .'were automatically paused.'
            );

        $this->assertFalse(
            $firstStation->refresh()->active
        );

        $this->assertFalse(
            $secondStation->refresh()->active
        );

        $this->assertFalse(
            $alreadyPausedStation->refresh()->active
        );

        $this->assertDatabaseMissing(
            'signing_station_device_leases',
            [
                'signing_station_id' =>
                    $firstStation->id,
            ]
        );

        $this->assertDatabaseMissing(
            'signing_station_device_leases',
            [
                'signing_station_id' =>
                    $secondStation->id,
            ]
        );
    }

    public function test_offline_template_blocks_kiosk_activation_with_clear_warning(): void
    {
        $template = $this->createPublishedTemplate();

        $station = $this->createStation(
            $template,
            'Paused Kiosk',
            false
        );

        $template->update([
            'active_version_id' => null,
            'status' => 'draft',
        ]);

        $this
            ->patch(
                route(
                    'signing-stations.toggle',
                    $station
                )
            )
            ->assertSessionHasErrors([
                'station' =>
                    'This signing station cannot be activated '
                    .'because its consent template is offline. '
                    .'Publish the template first.',
            ]);

        $this->assertFalse(
            $station->refresh()->active
        );
    }

    public function test_station_page_warns_when_assigned_template_is_offline_and_hides_launch_action(): void
    {
        $template = $this->createPublishedTemplate();

        $station = $this->createStation(
            $template,
            'Unavailable Kiosk',
            true
        );

        $template->update([
            'active_version_id' => null,
            'status' => 'draft',
        ]);

        $this
            ->get(
                route(
                    'signing-stations.show',
                    $station
                )
            )
            ->assertOk()
            ->assertSeeText(
                'This signing station is unavailable because '
                .'its assigned consent template is offline.'
            )
            ->assertSeeText(
                'Unavailable'
            )
            ->assertDontSeeText(
                'Launch Kiosk'
            );
    }

    public function test_expired_qr_window_warns_admin_but_shared_kiosk_remains_launchable(): void
    {
        $template = $this->createPublishedTemplate();

        $station = $this->createStation(
            $template,
            'Expired QR Kiosk',
            true
        );

        $station->update([
            'qr_expires_at' =>
                now()->subSecond(),
        ]);

        $this
            ->get(
                route(
                    'signing-stations.show',
                    $station
                )
            )
            ->assertOk()
            ->assertSeeText(
                'QR signing window has expired. '
                .'Renew it to allow new QR scans. '
                .'The shared kiosk can still be launched.'
            )
            ->assertSeeText(
                'Launch Kiosk'
            );
    }

    public function test_station_index_marks_active_station_with_offline_template_unavailable_and_hides_launch(): void
    {
        $template =
            $this->createPublishedTemplate();

        $station =
            $this->createStation(
                $template,
                'Legacy Offline Kiosk',
                true
            );

        $template->update([
            'active_version_id' =>
                null,

            'status' =>
                'draft',
        ]);

        $this
            ->get(
                route(
                    'signing-stations.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Legacy Offline Kiosk'
            )
            ->assertSeeText(
                'Unavailable'
            )
            ->assertDontSee(
                route(
                    'public-signing-stations.show',
                    $station->station_token
                ),
                false
            );
    }

    public function test_station_index_keeps_live_active_station_launchable(): void
    {
        $template =
            $this->createPublishedTemplate();

        $station =
            $this->createStation(
                $template,
                'Live Reception Kiosk',
                true
            );

        $this
            ->get(
                route(
                    'signing-stations.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Live Reception Kiosk'
            )
            ->assertSeeText(
                'Active'
            )
            ->assertDontSeeText(
                'Unavailable'
            )
            ->assertSee(
                route(
                    'public-signing-stations.show',
                    $station->station_token
                ),
                false
            );
    }

    public function test_offline_template_activation_warning_is_visible_on_station_page(): void
    {
        $template =
            $this->createPublishedTemplate();

        $station =
            $this->createStation(
                $template,
                'Warning Kiosk',
                false
            );

        $template->update([
            'active_version_id' =>
                null,

            'status' =>
                'draft',
        ]);

        $warning =
            'This signing station cannot be activated '
            .'because its consent template is offline. '
            .'Publish the template first.';

        $stationPage =
            route(
                'signing-stations.show',
                $station
            );

        /*
         * Exercise the real browser flow:
         *
         * station page
         *   -> Activate Station
         *   -> PATCH toggle
         *   -> return back()->withErrors(...)
         *   -> browser follows redirect
         *   -> warning is rendered
         */
        $response =
            $this
                ->from(
                    $stationPage
                )
                ->followingRedirects()
                ->patch(
                    route(
                        'signing-stations.toggle',
                        $station
                    )
                );

        $response
            ->assertOk()
            ->assertSeeText(
                'Signing station action could not be completed'
            )
            ->assertSeeText(
                $warning
            )
            ->assertSeeText(
                'Consent template offline'
            );

        $this->assertFalse(
            $station->refresh()->active
        );
    }

    public function test_active_station_with_offline_template_does_not_acquire_device_lease(): void
    {
        $template =
            $this->createPublishedTemplate();

        $station =
            $this->createStation(
                $template,
                'Stale Public Kiosk',
                true
            );

        $template->update([
            'active_version_id' =>
                null,

            'status' =>
                'draft',
        ]);

        $this
            ->withSession([
                \App\Services\SigningStationDeviceLeaseService::
                    SESSION_KEY =>
                        str_repeat(
                            'Z',
                            64
                        ),
            ])
            ->get(
                route(
                    'public-signing-stations.show',
                    $station->station_token
                )
            )
            ->assertNotFound();

        $this->assertDatabaseCount(
            'signing_station_device_leases',
            0
        );
    }

    public function test_republishing_template_does_not_reactivate_auto_paused_station(): void
    {
        $template =
            $this->createPublishedTemplate();

        $station =
            $this->createStation(
                $template,
                'Republish Kiosk',
                true
            );

        $this
            ->post(
                route(
                    'consent-templates.unpublish',
                    $template
                )
            )
            ->assertRedirect(
                route(
                    'consent-templates.manage'
                )
            );

        $this->assertFalse(
            $station->refresh()->active
        );

        $this
            ->post(
                route(
                    'consent-templates.publish',
                    $template
                )
            )
            ->assertRedirect(
                route(
                    'consent-templates.published',
                    $template
                )
            );

        $this->assertTrue(
            $template->refresh()->isLive()
        );

        $this->assertFalse(
            $station->refresh()->active
        );
    }

    private function createPublishedTemplate(): ConsentTemplate
    {
        $template = ConsentTemplate::query()->create([
            'organization_id' =>
                $this->organization->id,

            'title' =>
                'Public Kiosk Consent',

            'description' =>
                'Kiosk lifecycle regression consent.',

            'category' =>
                'Testing',

            'usage_type' =>
                ConsentTemplate::USAGE_SIGNING_STATION,

            'template_schema' => [
                'sections' => [],
            ],

            'active_version_id' =>
                null,

            'has_unpublished_changes' =>
                false,

            'status' =>
                'draft',
        ]);

        $version = $template
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

    private function createStation(
        ConsentTemplate $template,
        string $name,
        bool $active
    ): SigningStation {
        return SigningStation::query()->create([
            'organization_id' =>
                $this->organization->id,

            'consent_template_id' =>
                $template->id,

            'created_by' =>
                $this->administrator->id,

            'name' =>
                $name,

            'station_token' =>
                Str::random(64),

            'qr_expires_at' =>
                now()->addHours(24),

            'active' =>
                $active,

            'require_email' =>
                false,

            'require_reference' =>
                false,

            'auto_reset_seconds' =>
                3,
        ]);
    }

    private function createDeviceLease(
        SigningStation $station
    ): void {
        DB::table(
            'signing_station_device_leases'
        )->insert([
            'lease_token' =>
                (string) Str::uuid(),

            'organization_id' =>
                $this->organization->id,

            'signing_station_id' =>
                $station->id,

            'device_key' =>
                hash(
                    'sha256',
                    Str::random(64)
                ),

            'station_token_hash' =>
                hash(
                    'sha256',
                    $station->station_token
                ),

            'last_seen_at' =>
                now(),

            'expires_at' =>
                now()->addMinutes(3),

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);
    }
}
