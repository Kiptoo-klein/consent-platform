<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPaymentSetting;
use App\Services\ActivityLogger;
use App\Services\SubscriptionPaymentSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionPaymentSettingsController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function index(
        SubscriptionPaymentSettingsService $settingsService
    ): View {
        return view(
            'platform.subscription-payment-settings.index',
            [
                'settings' =>
                    $settingsService->settings(),
            ]
        );
    }

    public function update(
        Request $request,
        SubscriptionPaymentSettingsService $settingsService
    ): RedirectResponse {
        $validated = $request->validate([
            'mpesa_enabled' => [
                'required',
                'boolean',
            ],

            'mpesa_type' => [
                'nullable',
                'required_if:mpesa_enabled,1',
                'string',
                Rule::in([
                    'paybill',
                    'till',
                ]),
            ],

            'mpesa_business_number' => [
                'nullable',
                'required_if:mpesa_enabled,1',
                'string',
                'max:50',
            ],

            'mpesa_account_reference_instructions' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'mpesa_instructions' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'bank_enabled' => [
                'required',
                'boolean',
            ],

            'bank_name' => [
                'nullable',
                'required_if:bank_enabled,1',
                'string',
                'max:160',
            ],

            'bank_account_name' => [
                'nullable',
                'required_if:bank_enabled,1',
                'string',
                'max:200',
            ],

            'bank_account_number' => [
                'nullable',
                'required_if:bank_enabled,1',
                'string',
                'max:120',
            ],

            'bank_branch' => [
                'nullable',
                'string',
                'max:160',
            ],

            'bank_swift_code' => [
                'nullable',
                'string',
                'max:50',
            ],

            'bank_reference_instructions' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'bank_instructions' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'billing_contact_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'billing_contact_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'additional_instructions' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);

        $oldSettings =
            $settingsService->settings();

        $newSettings = [
            'mpesa_enabled' =>
                $request->boolean(
                    'mpesa_enabled'
                ),

            'mpesa_type' =>
                $this->nullableText(
                    $validated[
                        'mpesa_type'
                    ] ?? null
                ),

            'mpesa_business_number' =>
                $this->nullableText(
                    $validated[
                        'mpesa_business_number'
                    ] ?? null
                ),

            'mpesa_account_reference_instructions' =>
                $this->nullableText(
                    $validated[
                        'mpesa_account_reference_instructions'
                    ] ?? null
                ),

            'mpesa_instructions' =>
                $this->nullableText(
                    $validated[
                        'mpesa_instructions'
                    ] ?? null
                ),

            'bank_enabled' =>
                $request->boolean(
                    'bank_enabled'
                ),

            'bank_name' =>
                $this->nullableText(
                    $validated[
                        'bank_name'
                    ] ?? null
                ),

            'bank_account_name' =>
                $this->nullableText(
                    $validated[
                        'bank_account_name'
                    ] ?? null
                ),

            'bank_account_number' =>
                $this->nullableText(
                    $validated[
                        'bank_account_number'
                    ] ?? null
                ),

            'bank_branch' =>
                $this->nullableText(
                    $validated[
                        'bank_branch'
                    ] ?? null
                ),

            'bank_swift_code' =>
                $this->nullableText(
                    $validated[
                        'bank_swift_code'
                    ] ?? null
                ),

            'bank_reference_instructions' =>
                $this->nullableText(
                    $validated[
                        'bank_reference_instructions'
                    ] ?? null
                ),

            'bank_instructions' =>
                $this->nullableText(
                    $validated[
                        'bank_instructions'
                    ] ?? null
                ),

            'billing_contact_email' =>
                $this->nullableText(
                    $validated[
                        'billing_contact_email'
                    ] ?? null
                ),

            'billing_contact_phone' =>
                $this->nullableText(
                    $validated[
                        'billing_contact_phone'
                    ] ?? null
                ),

            'additional_instructions' =>
                $this->nullableText(
                    $validated[
                        'additional_instructions'
                    ] ?? null
                ),
        ];

        DB::transaction(
            function () use (
                $request,
                $oldSettings,
                $newSettings
            ): void {
                $setting =
                    SubscriptionPaymentSetting::
                        query()
                        ->where(
                            'singleton_key',
                            SubscriptionPaymentSetting::
                                SINGLETON_KEY
                        )
                        ->lockForUpdate()
                        ->first();

                if ($setting === null) {
                    $setting =
                        new SubscriptionPaymentSetting();

                    $setting->singleton_key =
                        SubscriptionPaymentSetting::
                            SINGLETON_KEY;
                }

                $setting->fill(
                    array_merge(
                        $newSettings,
                        [
                            'updated_by_user_id' =>
                                $request->user()->id,
                        ]
                    )
                );

                $setting->save();

                $this->activityLogger->log(
                    action:
                        'platform.subscription_payment_settings_updated',

                    description:
                        'Subscription payment settings were updated.',

                    subject:
                        $setting,

                    organizationId:
                        null,

                    properties: [
                        'old' =>
                            $oldSettings,

                        'new' =>
                            $newSettings,
                    ],
                );
            },
            3
        );

        $settingsService->forgetCache();

        return redirect()
            ->route(
                'platform.subscription-payment-settings.index'
            )
            ->with(
                'success',
                'Payment settings updated successfully.'
            );
    }

    private function nullableText(
        mixed $value
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
