<?php

namespace App\Http\Controllers\Platform;

use App\Enums\SubscriptionTransactionStatus;
use App\Enums\SubscriptionTransactionType;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionTransaction;
use App\Services\ActivityLogger;
use App\Services\SubscriptionWorkflowNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

class SubscriptionPaymentClaimController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly SubscriptionWorkflowNotificationService
            $notifications
    ) {
    }

    public function confirm(
        Request $request,
        Organization $organization,
        SubscriptionInvoice $subscriptionInvoice,
        SubscriptionTransactionController
            $transactionController
    ): RedirectResponse {
        $user = $request->user();

        abort_unless($user, 401);

        [$confirmedRequest, $transaction] =
            DB::transaction(
                function () use (
                    $request,
                    $user,
                    $organization,
                    $subscriptionInvoice,
                    $transactionController
                ): array {
                    $invoice = SubscriptionInvoice::query()
                        ->whereKey(
                            $subscriptionInvoice->id
                        )
                        ->where(
                            'organization_id',
                            $organization->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    $planRequest =
                        OrganizationSubscriptionPlanRequest::query()
                            ->where(
                                'subscription_invoice_id',
                                $invoice->id
                            )
                            ->where(
                                'organization_id',
                                $organization->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    abort_unless(
                        $planRequest
                            ->hasPendingPaymentClaim(),
                        422,
                        'No payment report is awaiting verification.'
                    );

                    $outstanding = $this->outstandingBalance(
                        $invoice
                    );

                    if (
                        abs(
                            (float) $planRequest
                                ->payment_claim_amount
                            - $outstanding
                        ) > 0.005
                    ) {
                        throw ValidationException::withMessages([
                            'payment_claim' =>
                                'The reported amount does not match the outstanding invoice balance.',
                        ]);
                    }

                    $syntheticRequest =
                        $request->duplicate(
                            null,
                            [
                                'reference' =>
                                    $planRequest
                                        ->payment_claim_reference,
                                'payment_method' =>
                                    $planRequest
                                        ->payment_claim_method,
                                'notes' =>
                                    $planRequest
                                        ->payment_claim_notes,
                            ]
                        );

                    $syntheticRequest->setUserResolver(
                        fn () => $user
                    );

                    $this->invokeLegacyPaymentRecorder(
                        controller:
                            $transactionController,
                        request:
                            $syntheticRequest,
                        organization:
                            $organization,
                        invoice:
                            $invoice
                    );

                    $transaction =
                        SubscriptionTransaction::query()
                            ->where(
                                'subscription_invoice_id',
                                $invoice->id
                            )
                            ->where(
                                'reference',
                                $planRequest
                                    ->payment_claim_reference
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    $transaction->forceFill([
                        'paid_at' =>
                            $planRequest
                                ->payment_claim_paid_at,
                        'payment_method' =>
                            $planRequest
                                ->payment_claim_method,
                        'notes' =>
                            $planRequest
                                ->payment_claim_notes,
                    ])->save();

                    $invoice->forceFill([
                        'paid_at' =>
                            $planRequest
                                ->payment_claim_paid_at,
                    ])->save();

                    $planRequest->refresh();
                    $reviewedAt = now()->startOfSecond();

                    $planRequest->forceFill([
                        'payment_claim_status' =>
                            OrganizationSubscriptionPlanRequest::
                                PAYMENT_CLAIM_CONFIRMED,
                        'payment_claim_reviewed_at' =>
                            $reviewedAt,
                        'payment_claim_reviewed_by_user_id' =>
                            $user->id,
                        'payment_claim_rejection_reason' =>
                            null,
                    ])->save();

                    $this->activityLogger->log(
                        action:
                            'platform.subscription_payment_claim_confirmed',
                        description:
                            'Platform Billing verified the reported payment and activated the requested subscription plan.',
                        subject: $planRequest,
                        organizationId:
                            $organization->id,
                        properties: [
                            'invoice_number' =>
                                $invoice->invoice_number,
                            'transaction_id' =>
                                $transaction->id,
                            'reference' =>
                                $transaction->reference,
                            'amount' =>
                                $transaction->amount,
                            'currency' =>
                                $transaction->currency,
                            'payment_method' =>
                                $transaction->payment_method,
                            'paid_at' =>
                                $transaction
                                    ->paid_at
                                    ?->toIso8601String(),
                            'confirmed_by_user_id' =>
                                $user->id,
                        ],
                    );

                    return [
                        $planRequest->refresh(),
                        $transaction->refresh(),
                    ];
                },
                3
            );

        try {
            $this->notifications->queueReceipt(
                $confirmedRequest,
                $transaction
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route(
                'platform.organizations.subscription-invoices.show',
                [
                    $organization,
                    $subscriptionInvoice,
                ]
            )
            ->with(
                'success',
                'Payment confirmed. The plan is active and the customer receipt was queued.'
            );
    }

    public function reject(
        Request $request,
        Organization $organization,
        SubscriptionInvoice $subscriptionInvoice
    ): RedirectResponse {
        $validated = $request->validate([
            'rejection_reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $user = $request->user();

        abort_unless($user, 401);

        $rejectedRequest = DB::transaction(
            function () use (
                $user,
                $organization,
                $subscriptionInvoice,
                $validated
            ): OrganizationSubscriptionPlanRequest {
                $invoice = SubscriptionInvoice::query()
                    ->whereKey(
                        $subscriptionInvoice->id
                    )
                    ->where(
                        'organization_id',
                        $organization->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $planRequest =
                    OrganizationSubscriptionPlanRequest::query()
                        ->where(
                            'subscription_invoice_id',
                            $invoice->id
                        )
                        ->where(
                            'organization_id',
                            $organization->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                abort_unless(
                    $planRequest
                        ->hasPendingPaymentClaim(),
                    422,
                    'No payment report is awaiting verification.'
                );

                $reviewedAt = now()->startOfSecond();

                $planRequest->forceFill([
                    'payment_claim_status' =>
                        OrganizationSubscriptionPlanRequest::
                            PAYMENT_CLAIM_REJECTED,
                    'payment_claim_reviewed_at' =>
                        $reviewedAt,
                    'payment_claim_reviewed_by_user_id' =>
                        $user->id,
                    'payment_claim_rejection_reason' =>
                        trim(
                            $validated[
                                'rejection_reason'
                            ]
                        ),
                ])->save();

                $this->activityLogger->log(
                    action:
                        'platform.subscription_payment_claim_rejected',
                    description:
                        'Platform Billing could not verify the reported subscription payment.',
                    subject: $planRequest,
                    organizationId:
                        $organization->id,
                    properties: [
                        'invoice_number' =>
                            $invoice->invoice_number,
                        'reference' =>
                            $planRequest
                                ->payment_claim_reference,
                        'rejection_reason' =>
                            $planRequest
                                ->payment_claim_rejection_reason,
                        'reviewed_by_user_id' =>
                            $user->id,
                    ],
                );

                return $planRequest->refresh();
            },
            3
        );

        try {
            $this->notifications->queuePaymentRejected(
                $rejectedRequest
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route(
                'platform.organizations.subscription-invoices.show',
                [
                    $organization,
                    $subscriptionInvoice,
                ]
            )
            ->with(
                'success',
                'The payment report was rejected. The organization may report it again or cancel the request.'
            );
    }

    private function invokeLegacyPaymentRecorder(
        SubscriptionTransactionController $controller,
        Request $request,
        Organization $organization,
        SubscriptionInvoice $invoice
    ): void {
        $method = new ReflectionMethod(
            $controller,
            'recordInvoicePayment'
        );

        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            abort_unless(
                $type instanceof ReflectionNamedType,
                500,
                'The existing invoice payment recorder has an unsupported signature.'
            );

            $arguments[] = match ($type->getName()) {
                Request::class => $request,
                Organization::class => $organization,
                SubscriptionInvoice::class => $invoice,
                default => abort(
                    500,
                    'The existing invoice payment recorder has an unsupported parameter.'
                ),
            };
        }

        $method->invokeArgs(
            $controller,
            $arguments
        );
    }

    private function outstandingBalance(
        SubscriptionInvoice $invoice
    ): float {
        $successfulPayments =
            (float) $invoice
                ->transactions()
                ->where(
                    'type',
                    SubscriptionTransactionType::
                        PAYMENT->value
                )
                ->where(
                    'status',
                    SubscriptionTransactionStatus::
                        SUCCESSFUL->value
                )
                ->sum('amount');

        return max(
            0,
            round(
                (float) $invoice->total_amount
                - $successfulPayments,
                2
            )
        );
    }
}
