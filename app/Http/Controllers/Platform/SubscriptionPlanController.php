<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    /**
     * Display editable subscription plans and limits.
     */
    public function index(): View
    {
        $plans = SubscriptionPlan::query()
            ->withCount('subscriptions')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view(
            'platform.subscription-plans.index',
            [
                'plans' => $plans,
            ]
        );
    }

    /**
     * Update one subscription plan.
     */
    public function update(
        Request $request,
        SubscriptionPlan $subscriptionPlan
    ): RedirectResponse {
        $request->merge([
            'currency' =>
                strtoupper(
                    trim(
                        (string) $request->input(
                            'currency',
                            'KES'
                        )
                    )
                ),

            'is_active' =>
                $request->boolean('is_active'),

            'annual_billing_enabled' =>
                $request->boolean(
                    'annual_billing_enabled'
                ),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'monthly_price' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:999999999.99',
                'decimal:0,2',
            ],

            'currency' => [
                'required',
                'string',
                'regex:/^[A-Z]{3}$/',
            ],

            'annual_billing_enabled' => [
                'required',
                'boolean',
            ],

            'annual_discount_percent' => [
                'required',
                'numeric',
                'between:0,50',
                'decimal:0,2',
            ],

            'max_users' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],

            'max_consent_managers' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'max_staff' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'max_auditors' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'max_active_kiosks' => [
                'required',
                'integer',
                'min:0',
                'max:65535',
            ],

            'max_consent_templates' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000000',
            ],

            'max_signed_consents_per_period' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000000',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        /*
         * Total seats include one Organization Admin.
         */
        $allocatedSeats =
            1
            + (int) $validated[
                'max_consent_managers'
            ]
            + (int) $validated['max_staff']
            + (int) $validated['max_auditors'];

        if (
            $allocatedSeats
            > (int) $validated['max_users']
        ) {
            throw ValidationException::withMessages([
                'max_users' =>
                    'Total users must cover one Organization Admin '
                    .'plus all Consent Manager, Staff, and Auditor '
                    .'role limits. The current role limits require at '
                    ."least {$allocatedSeats} total users.",
            ]);
        }

        $changed = DB::transaction(
            function () use (
                $request,
                $subscriptionPlan,
                $validated
            ): bool {
                $plan = SubscriptionPlan::query()
                    ->whereKey(
                        $subscriptionPlan->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $oldValues =
                    $this->planSnapshot($plan);

                $plan->update([
                    'name' =>
                        trim($validated['name']),

                    'description' =>
                        filled(
                            $validated['description']
                            ?? null
                        )
                            ? trim(
                                $validated[
                                    'description'
                                ]
                            )
                            : null,

                    'monthly_price' =>
                        filled(
                            $validated[
                                'monthly_price'
                            ]
                            ?? null
                        )
                            ? number_format(
                                (float) $validated[
                                    'monthly_price'
                                ],
                                2,
                                '.',
                                ''
                            )
                            : null,

                    'currency' =>
                        $validated['currency'],

                    'annual_billing_enabled' =>
                        (bool) $validated[
                            'annual_billing_enabled'
                        ],

                    'annual_discount_percent' =>
                        number_format(
                            (float) $validated[
                                'annual_discount_percent'
                            ],
                            2,
                            '.',
                            ''
                        ),

                    'max_users' =>
                        (int) $validated['max_users'],

                    'max_consent_managers' =>
                        (int) $validated[
                            'max_consent_managers'
                        ],

                    'max_staff' =>
                        (int) $validated['max_staff'],

                    'max_auditors' =>
                        (int) $validated[
                            'max_auditors'
                        ],

                    'max_active_kiosks' =>
                        (int) $validated[
                            'max_active_kiosks'
                        ],

                    'max_consent_templates' =>
                        filled(
                            $validated[
                                'max_consent_templates'
                            ]
                            ?? null
                        )
                            ? (int) $validated[
                                'max_consent_templates'
                            ]
                            : null,

                    'max_signed_consents_per_period' =>
                        filled(
                            $validated[
                                'max_signed_consents_per_period'
                            ]
                            ?? null
                        )
                            ? (int) $validated[
                                'max_signed_consents_per_period'
                            ]
                            : null,

                    'sort_order' =>
                        (int) $validated[
                            'sort_order'
                        ],

                    'is_active' =>
                        (bool) $validated[
                            'is_active'
                        ],
                ]);

                $plan->refresh();

                $newValues =
                    $this->planSnapshot($plan);

                if ($oldValues === $newValues) {
                    return false;
                }

                $this->activityLogger->log(
                    action:
                        'platform.subscription_plan_updated',

                    description:
                        "Subscription plan {$plan->name} updated.",

                    subject:
                        $plan,

                    organizationId:
                        null,

                    properties: [
                        'old' => $oldValues,
                        'new' => $newValues,

                        'updated_by_user_id' =>
                            $request->user()->id,
                    ],
                );

                return true;
            }
        );

        return redirect()
            ->route(
                'platform.subscription-plans.index'
            )
            ->with(
                'success',
                $changed
                    ? 'Subscription plan updated successfully.'
                    : 'No subscription plan changes were detected.'
            );
    }

    /**
     * Return auditable plan configuration values.
     *
     * @return array<string, mixed>
     */
    private function planSnapshot(
        SubscriptionPlan $plan
    ): array {
        return [
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,

            'monthly_price' =>
                $plan->monthly_price,

            'currency' =>
                $plan->currency,

            'annual_billing_enabled' =>
                (bool) $plan
                    ->annual_billing_enabled,

            'annual_discount_percent' =>
                $plan
                    ->annual_discount_percent,

            'annual_price' =>
                $plan->annualPrice(),

            'max_users' =>
                (int) $plan->max_users,

            'max_consent_managers' =>
                (int) $plan
                    ->max_consent_managers,

            'max_staff' =>
                (int) $plan->max_staff,

            'max_auditors' =>
                (int) $plan->max_auditors,

            'max_active_kiosks' =>
                (int) $plan
                    ->max_active_kiosks,

            'max_consent_templates' =>
                $plan->max_consent_templates === null
                    ? null
                    : (int) $plan
                        ->max_consent_templates,

            'max_signed_consents_per_period' =>
                $plan
                    ->max_signed_consents_per_period
                    === null
                        ? null
                        : (int) $plan
                            ->max_signed_consents_per_period,

            'sort_order' =>
                (int) $plan->sort_order,

            'is_active' =>
                (bool) $plan->is_active,
        ];
    }
}
