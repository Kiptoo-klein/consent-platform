<?php

namespace Tests\Feature;

use App\Models\PlatformBrandingSetting;
use App\Models\PlatformRole;
use App\Models\User;
use App\Services\PlatformBrandingSettingsService;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformBrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PlatformRoleSeeder::class
        );
    }

    public function test_only_super_admin_can_open_and_update_platform_branding(): void
    {
        $superAdmin =
            $this->platformUser(
                'super-admin'
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->get(
                route(
                    'platform.branding-settings.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-platform-branding-settings',
                false
            )
            ->assertSee(
                'data-branding-form="identity"',
                false
            )
            ->assertSee(
                'data-branding-form="assets"',
                false
            )
            ->assertSee(
                'data-branding-form="colors"',
                false
            )
            ->assertSee(
                'data-branding-form="contact"',
                false
            )
            ->assertSee(
                'data-branding-preview-panel',
                false
            );

        foreach ([
            'billing',
            'support',
            'platform-auditor',
        ] as $roleSlug) {
            $user =
                $this->platformUser(
                    $roleSlug
                );

            $this
                ->actingAs(
                    $user
                )
                ->get(
                    route(
                        'platform.branding-settings.index'
                    )
                )
                ->assertForbidden();

            $this
                ->actingAs(
                    $user
                )
                ->patch(
                    route(
                        'platform.branding-settings.update'
                    ),
                    [
                        'section' =>
                            'identity',

                        'platform_name' =>
                            'Blocked',

                        'short_name' =>
                            'BL',
                    ]
                )
                ->assertForbidden();
        }
    }

    public function test_identity_update_does_not_change_assets_colors_or_contact_fields(): void
    {
        $superAdmin =
            $this->platformUser(
                'super-admin'
            );

        $setting =
            $this->createSetting(
                $superAdmin
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->patch(
                route(
                    'platform.branding-settings.update'
                ),
                [
                    'section' =>
                        'identity',

                    'platform_name' =>
                        'Digital Hub',

                    'short_name' =>
                        'DH',

                    'tagline' =>
                        'Secure digital workflows.',

                    'description' =>
                        'Updated platform description.',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.branding-settings.index'
                )
            )
            ->assertSessionHas(
                'success_section',
                'identity'
            );

        $setting->refresh();

        $this->assertSame(
            'Digital Hub',
            $setting->platform_name
        );

        $this->assertSame(
            'DH',
            $setting->short_name
        );

        $this->assertSame(
            'platform-branding/logos/original.png',
            $setting->logo_path
        );

        $this->assertSame(
            '#123456',
            $setting->primary_color
        );

        $this->assertSame(
            'original@example.com',
            $setting->support_email
        );

        $this->assertSame(
            'Original footer.',
            $setting->footer_text
        );

        $this->assertSame(
            'Digital Hub',
            config('app.name')
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' =>
                    'platform.branding_settings_updated',
            ]
        );
    }

    public function test_asset_update_does_not_change_identity_colors_or_contact_fields(): void
    {
        Storage::fake(
            'public'
        );

        $superAdmin =
            $this->platformUser(
                'super-admin'
            );

        Storage::disk('public')->put(
            'platform-branding/logos/original.png',
            'old-logo'
        );

        Storage::disk('public')->put(
            'platform-branding/favicons/original.png',
            'old-favicon'
        );

        $setting =
            $this->createSetting(
                $superAdmin
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->patch(
                route(
                    'platform.branding-settings.update'
                ),
                [
                    'section' =>
                        'assets',

                    'logo' =>
                        UploadedFile::fake()
                            ->image(
                                'new-logo.png',
                                500,
                                200
                            ),

                    'favicon' =>
                        UploadedFile::fake()
                            ->image(
                                'new-favicon.png',
                                256,
                                256
                            ),
                ]
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success_section',
                'assets'
            );

        $setting->refresh();

        $this->assertSame(
            'Existing Platform',
            $setting->platform_name
        );

        $this->assertSame(
            '#123456',
            $setting->primary_color
        );

        $this->assertSame(
            'original@example.com',
            $setting->support_email
        );

        $this->assertNotSame(
            'platform-branding/logos/original.png',
            $setting->logo_path
        );

        $this->assertNotSame(
            'platform-branding/favicons/original.png',
            $setting->favicon_path
        );

        Storage::disk('public')
            ->assertExists(
                $setting->logo_path
            );

        Storage::disk('public')
            ->assertExists(
                $setting->favicon_path
            );

        Storage::disk('public')
            ->assertMissing(
                'platform-branding/logos/original.png'
            );

        Storage::disk('public')
            ->assertMissing(
                'platform-branding/favicons/original.png'
            );
    }

    public function test_logo_upload_preserves_and_renders_platform_name_in_navigation(): void
    {
        Storage::fake(
            'public'
        );

        $superAdmin =
            $this->platformUser(
                'super-admin'
            );

        $setting =
            $this->createSetting(
                $superAdmin,
                [
                    'platform_name' =>
                        'Menu Identity',

                    'short_name' =>
                        'MI',

                    'logo_path' =>
                        null,

                    'favicon_path' =>
                        null,
                ]
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->patch(
                route(
                    'platform.branding-settings.update'
                ),
                [
                    'section' =>
                        'assets',

                    'logo' =>
                        UploadedFile::fake()
                            ->image(
                                'menu-logo.png',
                                600,
                                240
                            ),
                ]
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success_section',
                'assets'
            );

        $setting->refresh();

        $this->assertSame(
            'Menu Identity',
            $setting->platform_name
        );

        $this->assertSame(
            'MI',
            $setting->short_name
        );

        $this->assertNotNull(
            $setting->logo_path
        );

        Storage::disk('public')
            ->assertExists(
                $setting->logo_path
            );

        app(
            PlatformBrandingSettingsService::class
        )->forgetCache();

        $this
            ->actingAs(
                $superAdmin
            )
            ->get(
                route(
                    'platform.dashboard'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-platform-logo',
                false
            )
            ->assertSee(
                'data-platform-brand-name',
                false
            )
            ->assertSeeText(
                'Menu Identity'
            );
    }

    public function test_logo_button_persists_logo_and_preserves_platform_name(): void
    {
        Storage::fake(
            'public'
        );

        $superAdmin =
            $this->platformUser(
                'super-admin'
            );

        $setting =
            $this->createSetting(
                $superAdmin,
                [
                    'platform_name' =>
                        'Persistent Platform Name',

                    'short_name' =>
                        'PP',

                    'logo_path' =>
                        null,
                ]
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->patch(
                route(
                    'platform.branding-settings.update'
                ),
                [
                    'section' =>
                        'logo',

                    'logo' =>
                        UploadedFile::fake()
                            ->image(
                                'persistent-logo.png',
                                640,
                                256
                            ),
                ]
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success_section',
                'logo'
            );

        $setting->refresh();

        $this->assertSame(
            'Persistent Platform Name',
            $setting->platform_name
        );

        $this->assertSame(
            'PP',
            $setting->short_name
        );

        $this->assertNotNull(
            $setting->logo_path
        );

        Storage::disk('public')
            ->assertExists(
                $setting->logo_path
            );

        app(
            PlatformBrandingSettingsService::class
        )->forgetCache();

        $branding =
            app(
                PlatformBrandingSettingsService::class
            )->viewData();

        $this->assertStringStartsWith(
            '/storage/platform-branding/logos/',
            $branding['logo_url']
        );
    }

    public function test_asset_only_update_initializes_other_fields_from_defaults(): void
    {
        Storage::fake(
            'public'
        );

        $superAdmin =
            $this->platformUser(
                'super-admin'
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->patch(
                route(
                    'platform.branding-settings.update'
                ),
                [
                    'section' =>
                        'assets',

                    'logo' =>
                        UploadedFile::fake()
                            ->image(
                                'first-logo.png',
                                500,
                                200
                            ),
                ]
            )
            ->assertRedirect();

        $setting =
            PlatformBrandingSetting::query()
                ->sole();

        $this->assertSame(
            'eConsent',
            $setting->platform_name
        );

        $this->assertSame(
            'eC',
            $setting->short_name
        );

        $this->assertSame(
            '#312E81',
            $setting->primary_color
        );

        $this->assertSame(
            '#4F46E5',
            $setting->accent_color
        );

        $this->assertNotNull(
            $setting->logo_path
        );
    }

    public function test_color_update_does_not_change_other_branding_sections(): void
    {
        $superAdmin =
            $this->platformUser(
                'super-admin'
            );

        $setting =
            $this->createSetting(
                $superAdmin
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->patch(
                route(
                    'platform.branding-settings.update'
                ),
                [
                    'section' =>
                        'colors',

                    'primary_color' =>
                        '#ABCDEF',

                    'accent_color' =>
                        '#FEDCBA',

                    'pdf_primary_color' =>
                        '#102030',
                ]
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success_section',
                'colors'
            );

        $setting->refresh();

        $this->assertSame(
            '#ABCDEF',
            $setting->primary_color
        );

        $this->assertSame(
            '#FEDCBA',
            $setting->accent_color
        );

        $this->assertSame(
            '#102030',
            $setting->pdf_primary_color
        );

        $this->assertSame(
            'Existing Platform',
            $setting->platform_name
        );

        $this->assertSame(
            'platform-branding/logos/original.png',
            $setting->logo_path
        );

        $this->assertSame(
            'original@example.com',
            $setting->support_email
        );
    }

    public function test_contact_update_does_not_change_identity_assets_or_colors(): void
    {
        $superAdmin =
            $this->platformUser(
                'super-admin'
            );

        $setting =
            $this->createSetting(
                $superAdmin
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->patch(
                route(
                    'platform.branding-settings.update'
                ),
                [
                    'section' =>
                        'contact',

                    'support_email' =>
                        'support@digitalhub.example',

                    'website_url' =>
                        'https://digitalhub.example',

                    'footer_text' =>
                        'Digital Hub platform services.',
                ]
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success_section',
                'contact'
            );

        $setting->refresh();

        $this->assertSame(
            'support@digitalhub.example',
            $setting->support_email
        );

        $this->assertSame(
            'https://digitalhub.example',
            $setting->website_url
        );

        $this->assertSame(
            'Digital Hub platform services.',
            $setting->footer_text
        );

        $this->assertSame(
            'Existing Platform',
            $setting->platform_name
        );

        $this->assertSame(
            '#123456',
            $setting->primary_color
        );

        $this->assertSame(
            'platform-branding/logos/original.png',
            $setting->logo_path
        );
    }

    public function test_each_section_validates_only_its_own_fields(): void
    {
        $superAdmin =
            $this->platformUser(
                'super-admin'
            );

        $this
            ->actingAs(
                $superAdmin
            )
            ->from(
                route(
                    'platform.branding-settings.index'
                )
            )
            ->patch(
                route(
                    'platform.branding-settings.update'
                ),
                [
                    'section' =>
                        'identity',

                    'platform_name' =>
                        '',

                    'short_name' =>
                        'EC',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.branding-settings.index'
                )
            )
            ->assertSessionHasErrors([
                'platform_name',
            ]);

        $this
            ->actingAs(
                $superAdmin
            )
            ->from(
                route(
                    'platform.branding-settings.index'
                )
            )
            ->patch(
                route(
                    'platform.branding-settings.update'
                ),
                [
                    'section' =>
                        'colors',

                    'primary_color' =>
                        'green',

                    'accent_color' =>
                        '#4F46E5',

                    'pdf_primary_color' =>
                        '#312E81',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.branding-settings.index'
                )
            )
            ->assertSessionHasErrors([
                'primary_color',
            ]);

        $this->assertDatabaseCount(
            'platform_branding_settings',
            0
        );
    }

    public function test_branding_service_uses_econsent_defaults(): void
    {
        $settings =
            app(
                PlatformBrandingSettingsService::class
            )->settings();

        $this->assertSame(
            'eConsent',
            $settings[
                'platform_name'
            ]
        );

        $this->assertSame(
            'eC',
            $settings[
                'short_name'
            ]
        );

        $this->assertSame(
            '#312E81',
            $settings[
                'primary_color'
            ]
        );
    }

    public function test_branding_settings_navigation_is_super_admin_only(): void
    {
        $this
            ->actingAs(
                $this->platformUser(
                    'super-admin'
                )
            )
            ->get(
                route(
                    'platform.dashboard'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-navigation-route="platform.branding-settings.index"',
                false
            );

        foreach ([
            'billing',
            'support',
            'platform-auditor',
        ] as $roleSlug) {
            $this
                ->actingAs(
                    $this->platformUser(
                        $roleSlug
                    )
                )
                ->get(
                    route(
                        'platform.dashboard'
                    )
                )
                ->assertOk()
                ->assertDontSee(
                    'data-navigation-route="platform.branding-settings.index"',
                    false
                );
        }
    }

    private function createSetting(
        User $updatedBy,
        array $overrides = []
    ): PlatformBrandingSetting {
        return PlatformBrandingSetting::query()
            ->forceCreate(
                array_merge(
                    [
                        'singleton_key' =>
                            PlatformBrandingSetting::
                                SINGLETON_KEY,

                        'platform_name' =>
                            'Existing Platform',

                        'short_name' =>
                            'EP',

                        'tagline' =>
                            'Original tagline.',

                        'description' =>
                            'Original description.',

                        'logo_path' =>
                            'platform-branding/logos/original.png',

                        'favicon_path' =>
                            'platform-branding/favicons/original.png',

                        'primary_color' =>
                            '#123456',

                        'accent_color' =>
                            '#654321',

                        'pdf_primary_color' =>
                            '#112233',

                        'support_email' =>
                            'original@example.com',

                        'website_url' =>
                            'https://original.example.com',

                        'footer_text' =>
                            'Original footer.',

                        'updated_by_user_id' =>
                            $updatedBy->id,
                    ],
                    $overrides
                )
            );
    }

    private function platformUser(
        string $roleSlug
    ): User {
        $role =
            PlatformRole::query()
                ->where(
                    'slug',
                    $roleSlug
                )
                ->firstOrFail();

        return User::factory()->create([
            'organization_id' =>
                null,

            'platform_role_id' =>
                $role->id,

            'is_active' =>
                true,
        ]);
    }
}
