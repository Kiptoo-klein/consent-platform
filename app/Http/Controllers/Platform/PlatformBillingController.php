<?php

namespace App\Http\Controllers\Platform;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SubscriptionInvoice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PlatformBillingController extends Controller
{
    /**
     * Display a central platform billing overview.
     */
    public function index(
        Request $request
    ): View {
        $search = trim(
            (string) $request->query(
                'search',
                ''
            )
        );

        $paymentStatus =
            $this->validEnumValue(
                (string) $request->query(
                    'payment_status',
                    ''
                ),
                SubscriptionPaymentStatus::cases()
            );

        $subscriptionStatus =
            $this->validEnumValue(
                (string) $request->query(
                    'subscription_status',
                    ''
                ),
                OrganizationSubscriptionStatus::cases()
            );

        $subscriptions =
            OrganizationSubscription::query()
                ->with([
                    'organization',
                    'plan',
                    'billingOwner',
                ])
                ->withCount([
                    'invoices as outstanding_invoices_count' =>
                        function ($query): void {
                            $query->whereIn(
                                'status',
                                [
                                    SubscriptionInvoiceStatus::
                                        ISSUED->value,

                                    SubscriptionInvoiceStatus::
                                        OVERDUE->value,
                                ]
                            );
                        },

                    'invoices as overdue_invoices_count' =>
                        function ($query): void {
                            $query->where(
                                'status',
                                SubscriptionInvoiceStatus::
                                    OVERDUE->value
                            );
                        },
                ])
                ->when(
                    $search !== '',
                    function ($query) use (
                        $search
                    ): void {
                        $query->where(
                            function ($query) use (
                                $search
                            ): void {
                                $query
                                    ->whereHas(
                                        'organization',
                                        function ($query) use (
                                            $search
                                        ): void {
                                            $query->where(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            );
                                        }
                                    )
                                    ->orWhereHas(
                                        'billingOwner',
                                        function ($query) use (
                                            $search
                                        ): void {
                                            $query
                                                ->where(
                                                    'name',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                                ->orWhere(
                                                    'email',
                                                    'like',
                                                    "%{$search}%"
                                                );
                                        }
                                    );
                            }
                        );
                    }
                )
                ->when(
                    $paymentStatus !== null,
                    fn ($query) =>
                        $query->where(
                            'payment_status',
                            $paymentStatus
                        )
                )
                ->when(
                    $subscriptionStatus !== null,
                    fn ($query) =>
                        $query->where(
                            'status',
                            $subscriptionStatus
                        )
                )
                ->orderByRaw(
                    'CASE WHEN payment_status = ? '
                    .'THEN 1 ELSE 0 END',
                    [
                        SubscriptionPaymentStatus::
                            PAID->value,
                    ]
                )
                ->orderByRaw(
                    'CASE WHEN current_period_ends_at '
                    .'IS NULL THEN 1 ELSE 0 END'
                )
                ->orderBy('current_period_ends_at')
                ->orderBy('id')
                ->paginate(20)
                ->withQueryString();

        $pendingPlanRequests =
            OrganizationSubscriptionPlanRequest::query()
                ->where(
                    'status',
                    OrganizationSubscriptionPlanRequest::
                        STATUS_PENDING
                )
                ->with([
                    'organization',
                    'subscription',
                    'currentPlan',
                    'requestedPlan',
                    'requestedBy',
                    'invoice',
                ])
                ->orderBy('requested_at')
                ->orderBy('id')
                ->get();

        $statistics = [
            'pending_plan_requests' =>
                $pendingPlanRequests->count(),

            'subscriptions' =>
                OrganizationSubscription::query()
                    ->count(),

            'paid' =>
                OrganizationSubscription::query()
                    ->where(
                        'payment_status',
                        SubscriptionPaymentStatus::
                            PAID->value
                    )
                    ->count(),

            'payment_attention' =>
                OrganizationSubscription::query()
                    ->whereIn(
                        'payment_status',
                        [
                            SubscriptionPaymentStatus::
                                UNPAID->value,

                            SubscriptionPaymentStatus::
                                PAST_DUE->value,

                            SubscriptionPaymentStatus::
                                FAILED->value,
                        ]
                    )
                    ->count(),

            'outstanding_invoices' =>
                SubscriptionInvoice::query()
                    ->whereIn(
                        'status',
                        [
                            SubscriptionInvoiceStatus::
                                ISSUED->value,

                            SubscriptionInvoiceStatus::
                                OVERDUE->value,
                        ]
                    )
                    ->count(),

            'overdue_invoices' =>
                SubscriptionInvoice::query()
                    ->where(
                        'status',
                        SubscriptionInvoiceStatus::
                            OVERDUE->value
                    )
                    ->count(),

            'without_subscription' =>
                Organization::query()
                    ->whereDoesntHave(
                        'subscription'
                    )
                    ->count(),
        ];

        return view(
            'platform.billing.index',
            [
                'subscriptions' =>
                    $subscriptions,

                'pendingPlanRequests' =>
                    $pendingPlanRequests,

                'statistics' =>
                    $statistics,

                'paymentStatuses' =>
                    SubscriptionPaymentStatus::cases(),

                'subscriptionStatuses' =>
                    OrganizationSubscriptionStatus::cases(),

                'search' =>
                    $search,

                'paymentStatus' =>
                    $paymentStatus,

                'subscriptionStatus' =>
                    $subscriptionStatus,
            ]
        );
    }

    /**
     * Return a submitted enum value only when it is supported.
     *
     * @param array<int, object> $cases
     */
    private function validEnumValue(
        string $value,
        array $cases
    ): ?string {
        if ($value === '') {
            return null;
        }

        foreach ($cases as $case) {
            if ($case->value === $value) {
                return $value;
            }
        }

        return null;
    }
}
