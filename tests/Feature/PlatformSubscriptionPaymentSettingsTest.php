<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\SubscriptionPaymentSetting;
use App\Models\User;
use App\Services\SubscriptionPaymentSettingsService;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformSubscriptionPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;

    private User $organizationUser;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->seed(
            PlatformRoleSeeder::class
        );

        $superAdminRole =
            PlatformRole::query()
                ->where(
                    'slug',
                    'super-admin'
                )
                ->firstOrFail();

        $this->platformAdmin =
            User::factory()->create([
                'organization_id' =>
                    null,

                'platform_role_id' =>
                    $superAdminRole->id,

                'is_active' =>
                    true,
            ]);

        $organization =
            Organization::query()->create([
                'name' =>
                    'Payment Settings Clinic',

                'slug' =>
                    'payment-settings-clinic-'
                    .Str::lower(
                        Str::random(8)
                    ),
            ]);

        $this->organizationUser =
            User::factory()->create([
                'organization_id' =>
                    $organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);
    }

    public function test_platform_admin_can_view_payment_settings_page(): void
    {
        $this
            ->actingAs(
                $this->platformAdmin
            )
            ->get(
                $this->settingsUrl()
            )
            ->assertOk()
            ->assertSeeText(
                'Payment Settings'
            )
            ->assertSee(
                'name="mpesa_enabled"',
                false
            )
            ->assertSee(
                'name="mpesa_business_number"',
                false
            )
            ->assertSee(
                'name="bank_account_number"',
                false
            )
            ->assertSee(
                'name="billing_contact_email"',
                false
            );

        $this
            ->actingAs(
                $this->platformAdmin
            )
            ->get(
                route(
                    'platform.billing.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Payment Settings'
            )
            ->assertSee(
                $this->settingsUrl(),
                false
            );

        $this->assertDatabaseCount(
            'subscription_payment_settings',
            0
        );
    }


    public function test_platform_navigation_contains_payment_settings_link(): void
    {
        $this
            ->actingAs(
                $this->platformAdmin
            )
            ->get(
                route(
                    'platform.dashboard'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Payment Settings'
            )
            ->assertSee(
                $this->settingsUrl(),
                false
            );
    }

    public function test_organization_user_cannot_manage_payment_settings(): void
    {
        $this
            ->actingAs(
                $this->organizationUser
            )
            ->get(
                $this->settingsUrl()
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $this->organizationUser
            )
            ->patch(
                $this->settingsUrl(),
                $this->validPayload()
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'subscription_payment_settings',
            0
        );
    }

    public function test_platform_admin_can_update_payment_settings(): void
    {
        $settingsService =
            app(
                SubscriptionPaymentSettingsService::
                    class
            );

        $this->assertFalse(
            $settingsService
                ->settings()[
                    'mpesa_enabled'
                ]
        );

        $this
            ->actingAs(
                $this->platformAdmin
            )
            ->patch(
                $this->settingsUrl(),
                $this->validPayload()
            )
            ->assertRedirect(
                $this->settingsUrl()
            )
            ->assertSessionHas(
                'success',
                'Payment settings updated successfully.'
            );

        $setting =
            SubscriptionPaymentSetting::
                query()
                ->sole();

        $this->assertTrue(
            $setting->mpesa_enabled
        );

        $this->assertSame(
            'paybill',
            $setting->mpesa_type
        );

        $this->assertSame(
            '400200',
            $setting
                ->mpesa_business_number
        );

        $this->assertTrue(
            $setting->bank_enabled
        );

        $this->assertSame(
            'eConsent Holdings',
            $setting->bank_account_name
        );

        $this->assertSame(
            '0102030405',
            $setting->bank_account_number
        );

        $this->assertSame(
            $this->platformAdmin->id,
            $setting->updated_by_user_id
        );

        $this->assertTrue(
            $settingsService
                ->settings()[
                    'mpesa_enabled'
                ]
        );

        $activity =
            ActivityLog::query()
                ->where(
                    'action',
                    'platform.subscription_payment_settings_updated'
                )
                ->sole();

        $this->assertNull(
            $activity->organization_id
        );

        $this->assertSame(
            $this->platformAdmin->id,
            $activity->user_id
        );

        $this->assertSame(
            '400200',
            data_get(
                $activity->properties,
                'new.mpesa_business_number'
            )
        );
    }

    public function test_enabled_payment_channels_require_core_details(): void
    {
        $response = $this
            ->actingAs(
                $this->platformAdmin
            )
            ->from(
                $this->settingsUrl()
            )
            ->patch(
                $this->settingsUrl(),
                $this->validPayload([
                    'mpesa_business_number' =>
                        '',

                    'bank_name' =>
                        '',

                    'bank_account_name' =>
                        '',

                    'bank_account_number' =>
                        '',
                ])
            );

        $response
            ->assertRedirect(
                $this->settingsUrl()
            )
            ->assertSessionHasErrors([
                'mpesa_business_number',
                'bank_name',
                'bank_account_name',
                'bank_account_number',
            ]);

        $this->assertDatabaseCount(
            'subscription_payment_settings',
            0
        );
    }

    private function settingsUrl(): string
    {
        return route(
            'platform.subscription-payment-settings.index'
        );
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function validPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'mpesa_enabled' =>
                    '1',

                'mpesa_type' =>
                    'paybill',

                'mpesa_business_number' =>
                    '400200',

                'mpesa_account_reference_instructions' =>
                    'Use the invoice number as the account reference.',

                'mpesa_instructions' =>
                    'Keep the confirmation message.',

                'bank_enabled' =>
                    '1',

                'bank_name' =>
                    'Example Commercial Bank',

                'bank_account_name' =>
                    'eConsent Holdings',

                'bank_account_number' =>
                    '0102030405',

                'bank_branch' =>
                    'Nairobi',

                'bank_swift_code' =>
                    'EXAMPLEKX',

                'bank_reference_instructions' =>
                    'Use the invoice number as the transfer reference.',

                'bank_instructions' =>
                    'Bank charges are paid by the sender.',

                'billing_contact_email' =>
                    'billing@example.com',

                'billing_contact_phone' =>
                    '+254700000000',

                'additional_instructions' =>
                    'Send payment confirmation to the billing contact.',
            ],
            $overrides
        );
    }
}
