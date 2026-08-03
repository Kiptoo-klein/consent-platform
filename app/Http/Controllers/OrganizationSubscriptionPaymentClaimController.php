<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionTransactionStatus;
use App\Enums\SubscriptionTransactionType;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionTransaction;
use App\Services\ActivityLogger;
use App\Services\SubscriptionWorkflowNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrganizationSubscriptionPaymentClaimController
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly SubscriptionWorkflowNotificationService
            $notifications
    ) {
    }

    public function store(
        Request $request,
        OrganizationSubscriptionPlanRequest $planRequest
    ): RedirectResponse {
        $this->authorizeBillingUser(
            $request,
            $planRequest
        );

        $planRequest->loadMissing(
            'invoice'
        );

        $paymentDetails =
            $planRequest
                ->invoice
                ?->payment_details_snapshot
            ?? [];

        $allowedPaymentMethods =
            array_values(
                array_filter([
                    (bool) data_get(
                        $paymentDetails,
                        'mpesa_enabled',
                        false
                    )
                        ? 'M-Pesa'
                        : null,

                    (bool) data_get(
                        $paymentDetails,
                        'bank_enabled',
                        false
                    )
                        ? 'Bank Transfer'
                        : null,
                ])
            );

        $validated = $request->validate([
            'payment_method' => [
                'required',
                'string',
                'max:100',
                Rule::in(
                    $allowedPaymentMethods
                ),
            ],
            'reference' => [
                'required',
                'string',
                'max:120',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:9999999999.99',
            ],
            'paid_at' => [
                'required',
                'date',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'payment_method.in' =>
                'Select one of the payment methods available on this invoice.',
        ]);

        $paidAt = CarbonImmutable::parse(
            $validated['paid_at'],
            config('app.timezone')
        )->startOfSecond();

        if (
            $paidAt->greaterThan(
                now()->addMinutes(5)
            )
        ) {
            throw ValidationException::withMessages([
                'paid_at' =>
                    'The payment time cannot be in the future.',
            ]);
        }

        $reference = trim($validated['reference']);
        $paymentMethod = trim(
            $validated['payment_method']
        );

        $updatedRequest = DB::transaction(
            function () use (
                $request,
                $planRequest,
                $validated,
                $paidAt,
                $reference,
                $paymentMethod
            ): OrganizationSubscriptionPlanRequest {
                $lockedRequest =
                    OrganizationSubscriptionPlanRequest::query()
                        ->whereKey($planRequest->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->authorizeBillingUser(
                    $request,
                    $lockedRequest
                );

                abort_unless(
                    $lockedRequest->isPending()
                    && $lockedRequest->resolved_at === null,
                    422,
                    'This subscription request is no longer awaiting payment.'
                );

                abort_if(
                    $lockedRequest->hasPendingPaymentClaim(),
                    422,
                    'A payment report is already awaiting verification.'
                );

                $invoice = SubscriptionInvoice::query()
                    ->whereKey(
                        $lockedRequest
                            ->subscription_invoice_id
                    )
                    ->where(
                        'organization_id',
                        $lockedRequest->organization_id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless(
                    in_array(
                        $invoice->status,
                        [
                            SubscriptionInvoiceStatus::ISSUED,
                            SubscriptionInvoiceStatus::OVERDUE,
                        ],
                        true
                    )
                    && $invoice->paid_at === null,
                    422,
                    'This invoice is not awaiting payment.'
                );

                $outstanding = $this->outstandingBalance(
                    $invoice
                );

                if (
                    abs(
                        (float) $validated['amount']
                        - $outstanding
                    ) > 0.005
                ) {
                    throw ValidationException::withMessages([
                        'amount' =>
                            'The amount paid must match the outstanding invoice amount of '
                            .$invoice->currency
                            .' '
                            .number_format(
                                $outstanding,
                                2
                            )
                            .'.',
                    ]);
                }

                $transactionExists =
                    SubscriptionTransaction::query()
                        ->where(
                            'reference',
                            $reference
                        )
                        ->exists();

                $otherPendingClaimExists =
                    OrganizationSubscriptionPlanRequest::query()
                        ->where(
                            'id',
                            '!=',
                            $lockedRequest->id
                        )
                        ->where(
                            'payment_claim_status',
                            OrganizationSubscriptionPlanRequest::
                                PAYMENT_CLAIM_PENDING
                        )
                        ->where(
                            'payment_claim_reference',
                            $reference
                        )
                        ->exists();

                if (
                    $transactionExists
                    || $otherPendingClaimExists
                ) {
                    throw ValidationException::withMessages([
                        'reference' =>
                            'This payment reference has already been used.',
                    ]);
                }

                $submittedAt = now()->startOfSecond();

                $lockedRequest->forceFill([
                    'payment_claim_status' =>
                        OrganizationSubscriptionPlanRequest::
                            PAYMENT_CLAIM_PENDING,
                    'payment_claim_amount' =>
                        number_format(
                            $outstanding,
                            2,
                            '.',
                            ''
                        ),
                    'payment_claim_currency' =>
                        $invoice->currency,
                    'payment_claim_method' =>
                        $paymentMethod,
                    'payment_claim_reference' =>
                        $reference,
                    'payment_claim_paid_at' =>
                        $paidAt,
                    'payment_claim_notes' =>
                        filled($validated['notes'] ?? null)
                            ? trim($validated['notes'])
                            : null,
                    'payment_claim_submitted_at' =>
                        $submittedAt,
                    'payment_claim_submitted_by_user_id' =>
                        $request->user()->id,
                    'payment_claim_reviewed_at' => null,
                    'payment_claim_reviewed_by_user_id' => null,
                    'payment_claim_rejection_reason' => null,
                ])->save();

                $this->activityLogger->log(
                    action:
                        'organization.subscription_payment_reported',
                    description:
                        'The organization reported a subscription invoice payment for Platform Billing verification.',
                    subject: $lockedRequest,
                    organizationId:
                        $lockedRequest->organization_id,
                    properties: [
                        'invoice_number' =>
                            $invoice->invoice_number,
                        'amount' =>
                            $lockedRequest->payment_claim_amount,
                        'currency' =>
                            $lockedRequest->payment_claim_currency,
                        'payment_method' =>
                            $lockedRequest->payment_claim_method,
                        'reference' =>
                            $lockedRequest->payment_claim_reference,
                        'paid_at' =>
                            $lockedRequest
                                ->payment_claim_paid_at
                                ?->toIso8601String(),
                    ],
                );

                return $lockedRequest->refresh();
            },
            3
        );

        try {
            $this->notifications->queuePaymentReported(
                $updatedRequest
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route(
                'organization-billing.invoices.show',
                $updatedRequest->invoice
            )
            ->with(
                'success',
                'Payment reported. Platform Billing will verify it before activating the plan.'
            );
    }

    public function cancel(
        Request $request,
        OrganizationSubscriptionPlanRequest $planRequest
    ): RedirectResponse {
        $this->authorizeBillingUser(
            $request,
            $planRequest
        );

        $cancelledRequest = DB::transaction(
            function () use (
                $request,
                $planRequest
            ): OrganizationSubscriptionPlanRequest {
                $lockedRequest =
                    OrganizationSubscriptionPlanRequest::query()
                        ->whereKey($planRequest->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->authorizeBillingUser(
                    $request,
                    $lockedRequest
                );

                abort_unless(
                    $lockedRequest->isPending()
                    && $lockedRequest->resolved_at === null,
                    422,
                    'This subscription request can no longer be cancelled.'
                );

                abort_if(
                    $lockedRequest->hasPendingPaymentClaim(),
                    422,
                    'The request cannot be cancelled while payment verification is pending.'
                );

                $invoice = SubscriptionInvoice::query()
                    ->whereKey(
                        $lockedRequest
                            ->subscription_invoice_id
                    )
                    ->where(
                        'organization_id',
                        $lockedRequest->organization_id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_unless(
                    in_array(
                        $invoice->status,
                        [
                            SubscriptionInvoiceStatus::DRAFT,
                            SubscriptionInvoiceStatus::ISSUED,
                            SubscriptionInvoiceStatus::OVERDUE,
                        ],
                        true
                    )
                    && $invoice->paid_at === null,
                    422,
                    'This invoice can no longer be cancelled.'
                );

                $cancelledAt = now()->startOfSecond();

                $lockedRequest->forceFill([
                    'status' =>
                        OrganizationSubscriptionPlanRequest::
                            STATUS_CANCELLED,
                    'resolved_at' => $cancelledAt,
                    'resolved_by_user_id' =>
                        $request->user()->id,
                ])->save();

                $invoice->forceFill([
                    'status' =>
                        SubscriptionInvoiceStatus::CANCELLED,
                    'cancelled_at' => $cancelledAt,
                ])->save();

                $this->activityLogger->log(
                    action:
                        'organization.subscription_plan_request_cancelled_after_issue',
                    description:
                        'The organization cancelled an automatically issued subscription plan request before reporting payment.',
                    subject: $lockedRequest,
                    organizationId:
                        $lockedRequest->organization_id,
                    properties: [
                        'invoice_number' =>
                            $invoice->invoice_number,
                        'cancelled_by_user_id' =>
                            $request->user()->id,
                    ],
                );

                return $lockedRequest->refresh();
            },
            3
        );

        try {
            $this->notifications->queueCancellation(
                $cancelledRequest
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route(
                'organization-subscription-plans.index'
            )
            ->with(
                'success',
                'The subscription request and invoice were cancelled.'
            );
    }

    private function authorizeBillingUser(
        Request $request,
        OrganizationSubscriptionPlanRequest $planRequest
    ): void {
        $user = $request->user();

        abort_unless(
            $user
            && (bool) $user->is_active
            && $user->organization_id
                === $planRequest->organization_id,
            403
        );

        $subscription =
            OrganizationSubscription::query()
                ->where(
                    'organization_id',
                    $planRequest->organization_id
                )
                ->firstOrFail();

        $roleSlugs =
            method_exists($user, 'getRoleNames')
                ? $user
                    ->getRoleNames()
                    ->map(
                        fn ($role): string =>
                            Str::slug((string) $role)
                    )
                : collect();

        $isOrganizationAdministrator =
            $roleSlugs->contains(
                fn (string $role): bool =>
                    in_array(
                        $role,
                        [
                            'organization-admin',
                            'organization-administrator',
                            'organisation-admin',
                            'organisation-administrator',
                        ],
                        true
                    )
            );

        abort_unless(
            $subscription->billing_owner_user_id
                === $user->id
            || $isOrganizationAdministrator,
            403
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
