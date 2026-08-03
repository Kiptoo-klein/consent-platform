<?php

namespace App\Http\Controllers;

use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use App\Services\AutomaticSubscriptionWorkflowService;
use App\Services\SubscriptionWorkflowNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class AutomaticOrganizationSubscriptionPlanController
{
    public function __construct(
        private readonly AutomaticSubscriptionWorkflowService
            $workflow,
        private readonly SubscriptionWorkflowNotificationService
            $notifications
    ) {
    }

    public function __invoke(
        Request $request,
        SubscriptionPlan $subscriptionPlan,
        OrganizationSubscriptionPlanController
            $legacyController
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user, 401);

        $existingIds =
            OrganizationSubscriptionPlanRequest::query()
                ->where(
                    'organization_id',
                    $user->organization_id
                )
                ->pluck('id')
                ->all();

        [$legacyResponse, $planRequest] =
            DB::transaction(
                function () use (
                    $request,
                    $subscriptionPlan,
                    $legacyController,
                    $existingIds,
                    $user
                ): array {
                    $legacyResponse =
                        $legacyController->store(
                            $request,
                            $subscriptionPlan
                        );

                    $query =
                        OrganizationSubscriptionPlanRequest::query()
                            ->where(
                                'organization_id',
                                $user->organization_id
                            )
                            ->where(
                                'requested_subscription_plan_id',
                                $subscriptionPlan->id
                            )
                            ->where(
                                'requested_by_user_id',
                                $user->id
                            )
                            ->where(
                                'status',
                                OrganizationSubscriptionPlanRequest::
                                    STATUS_PENDING
                            )
                            ->latest('id');

                    if ($existingIds !== []) {
                        $query->whereNotIn(
                            'id',
                            $existingIds
                        );
                    }

                    $planRequest = $query->first();

                    if ($planRequest === null) {
                        return [
                            $legacyResponse,
                            null,
                        ];
                    }

                    $invoice =
                        $this->workflow->issueInvoice(
                            $planRequest,
                            $user
                        );

                    $planRequest->setRelation(
                        'invoice',
                        $invoice
                    );

                    return [
                        $legacyResponse,
                        $planRequest,
                    ];
                },
                3
            );

        if ($planRequest === null) {
            return $legacyResponse;
        }

        try {
            $this->notifications
                ->queueInitialNotifications(
                    $planRequest->refresh()
                );
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route(
                'organization-billing.invoices.show',
                $planRequest->invoice
            )
            ->with(
                'success',
                'Your plan was selected and the invoice was issued. Follow the payment instructions, then use I Have Paid.'
            );
    }
}
