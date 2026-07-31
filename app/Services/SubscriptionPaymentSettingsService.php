<?php

namespace App\Services;

use App\Models\SubscriptionPaymentSetting;
use Illuminate\Support\Facades\Cache;

class SubscriptionPaymentSettingsService
{
    public const CACHE_KEY =
        'subscription_payment_settings';

    /**
     * @return array{
     *     mpesa_enabled: bool,
     *     mpesa_type: ?string,
     *     mpesa_business_number: ?string,
     *     mpesa_account_reference_instructions: ?string,
     *     mpesa_instructions: ?string,
     *     bank_enabled: bool,
     *     bank_name: ?string,
     *     bank_account_name: ?string,
     *     bank_account_number: ?string,
     *     bank_branch: ?string,
     *     bank_swift_code: ?string,
     *     bank_reference_instructions: ?string,
     *     bank_instructions: ?string,
     *     billing_contact_email: ?string,
     *     billing_contact_phone: ?string,
     *     additional_instructions: ?string
     * }
     */
    public function settings(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            function (): array {
                $setting =
                    SubscriptionPaymentSetting::
                        query()
                        ->where(
                            'singleton_key',
                            SubscriptionPaymentSetting::
                                SINGLETON_KEY
                        )
                        ->first();

                if ($setting === null) {
                    return $this->defaults();
                }

                return [
                    'mpesa_enabled' =>
                        (bool) $setting
                            ->mpesa_enabled,

                    'mpesa_type' =>
                        $setting->mpesa_type,

                    'mpesa_business_number' =>
                        $setting
                            ->mpesa_business_number,

                    'mpesa_account_reference_instructions' =>
                        $setting
                            ->mpesa_account_reference_instructions,

                    'mpesa_instructions' =>
                        $setting
                            ->mpesa_instructions,

                    'bank_enabled' =>
                        (bool) $setting
                            ->bank_enabled,

                    'bank_name' =>
                        $setting->bank_name,

                    'bank_account_name' =>
                        $setting
                            ->bank_account_name,

                    'bank_account_number' =>
                        $setting
                            ->bank_account_number,

                    'bank_branch' =>
                        $setting->bank_branch,

                    'bank_swift_code' =>
                        $setting
                            ->bank_swift_code,

                    'bank_reference_instructions' =>
                        $setting
                            ->bank_reference_instructions,

                    'bank_instructions' =>
                        $setting
                            ->bank_instructions,

                    'billing_contact_email' =>
                        $setting
                            ->billing_contact_email,

                    'billing_contact_phone' =>
                        $setting
                            ->billing_contact_phone,

                    'additional_instructions' =>
                        $setting
                            ->additional_instructions,
                ];
            }
        );
    }

    public function forgetCache(): void
    {
        Cache::forget(
            self::CACHE_KEY
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'mpesa_enabled' =>
                false,

            'mpesa_type' =>
                null,

            'mpesa_business_number' =>
                null,

            'mpesa_account_reference_instructions' =>
                null,

            'mpesa_instructions' =>
                null,

            'bank_enabled' =>
                false,

            'bank_name' =>
                null,

            'bank_account_name' =>
                null,

            'bank_account_number' =>
                null,

            'bank_branch' =>
                null,

            'bank_swift_code' =>
                null,

            'bank_reference_instructions' =>
                null,

            'bank_instructions' =>
                null,

            'billing_contact_email' =>
                null,

            'billing_contact_phone' =>
                null,

            'additional_instructions' =>
                null,
        ];
    }
}
