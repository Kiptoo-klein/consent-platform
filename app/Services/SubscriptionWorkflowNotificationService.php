<?php

namespace App\Services;

use App\Jobs\SendSubscriptionWorkflowNotificationJob;
use App\Mail\SubscriptionWorkflowMail;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SubscriptionTransaction;
use App\Models\SubscriptionWorkflowNotification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SubscriptionWorkflowNotificationService
{
    public function queueInitialNotifications(
        OrganizationSubscriptionPlanRequest $planRequest
    ): void {
        $planRequest->loadMissing([
            'organization',
            'requestedPlan',
            'requestedBy',
            'invoice.subscription.billingOwner',
        ]);

        $this->queueCustomer(
            event:
                SubscriptionWorkflowNotification::
                    EVENT_INVOICE_READY_CUSTOMER,
            planRequest: $planRequest,
            version: 'initial-'.$planRequest->id
        );

        $this->queuePlatform(
            event:
                SubscriptionWorkflowNotification::
                    EVENT_PLAN_SELECTED_PLATFORM,
            planRequest: $planRequest,
            version: 'initial-'.$planRequest->id
        );
    }

    public function queuePaymentReported(
        OrganizationSubscriptionPlanRequest $planRequest
    ): void {
        $this->queuePlatform(
            event:
                SubscriptionWorkflowNotification::
                    EVENT_PAYMENT_REPORTED_PLATFORM,
            planRequest: $planRequest,
            version:
                'submitted-'
                .(
                    $planRequest
                        ->payment_claim_submitted_at
                        ?->format('U.u')
                    ?? $planRequest->updated_at?->format('U.u')
                    ?? $planRequest->id
                )
        );
    }

    public function queueCancellation(
        OrganizationSubscriptionPlanRequest $planRequest
    ): void {
        $this->queuePlatform(
            event:
                SubscriptionWorkflowNotification::
                    EVENT_REQUEST_CANCELLED_PLATFORM,
            planRequest: $planRequest,
            version:
                'cancelled-'
                .(
                    $planRequest->resolved_at?->format('U.u')
                    ?? $planRequest->updated_at?->format('U.u')
                    ?? $planRequest->id
                )
        );
    }

    public function queuePaymentRejected(
        OrganizationSubscriptionPlanRequest $planRequest
    ): void {
        $this->queueCustomer(
            event:
                SubscriptionWorkflowNotification::
                    EVENT_PAYMENT_REJECTED_CUSTOMER,
            planRequest: $planRequest,
            version:
                'rejected-'
                .(
                    $planRequest
                        ->payment_claim_reviewed_at
                        ?->format('U.u')
                    ?? $planRequest->updated_at?->format('U.u')
                    ?? $planRequest->id
                )
        );
    }

    public function queueReceipt(
        OrganizationSubscriptionPlanRequest $planRequest,
        SubscriptionTransaction $transaction
    ): void {
        $this->queueCustomer(
            event:
                SubscriptionWorkflowNotification::
                    EVENT_PAYMENT_RECEIPT_CUSTOMER,
            planRequest: $planRequest,
            version: 'receipt-'.$transaction->id,
            transaction: $transaction
        );
    }

    public function deliverQueued(
        int $notificationId
    ): void {
        $notification =
            SubscriptionWorkflowNotification::query()
                ->with([
                    'organization',
                    'invoice.organization',
                    'invoice.plan',
                    'invoice.issuedBy',
                    'invoice.transactions',
                    'invoice.planRequest.requestedPlan',
                    'invoice.planRequest.requestedBy',
                    'invoice.subscription.billingOwner',
                    'planRequest.requestedPlan',
                    'planRequest.requestedBy',
                    'planRequest.paymentClaimSubmittedBy',
                    'planRequest.paymentClaimReviewedBy',
                    'transaction.organization',
                    'transaction.plan',
                    'transaction.recordedBy',
                    'transaction.invoice.planRequest',
                ])
                ->findOrFail($notificationId);

        if ($notification->isSent()) {
            return;
        }

        $notification->forceFill([
            'status' =>
                SubscriptionWorkflowNotification::
                    STATUS_PROCESSING,
            'failed_at' => null,
            'error_message' => null,
        ])->save();

        try {
            Mail::to(
                $notification->recipient_email
            )->send(
                new SubscriptionWorkflowMail(
                    $notification
                )
            );

            $notification->forceFill([
                'status' =>
                    SubscriptionWorkflowNotification::
                        STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();
        } catch (Throwable $exception) {
            $notification->forceFill([
                'status' =>
                    SubscriptionWorkflowNotification::
                        STATUS_FAILED,
                'sent_at' => null,
                'failed_at' => now(),
                'error_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    2000
                ),
            ])->save();

            throw $exception;
        }
    }

    private function queueCustomer(
        string $event,
        OrganizationSubscriptionPlanRequest $planRequest,
        string $version,
        ?SubscriptionTransaction $transaction = null
    ): void {
        $planRequest->loadMissing([
            'organization',
            'requestedPlan',
            'requestedBy',
            'invoice.subscription.billingOwner',
        ]);

        $recipient = $this->customerRecipient(
            $planRequest
        );

        if ($recipient === null) {
            return;
        }

        $this->createAndDispatch(
            event: $event,
            planRequest: $planRequest,
            recipientUserId: $recipient['user_id'],
            recipientEmail: $recipient['email'],
            version: $version,
            transaction: $transaction
        );
    }

    private function queuePlatform(
        string $event,
        OrganizationSubscriptionPlanRequest $planRequest,
        string $version
    ): void {
        $planRequest->loadMissing([
            'organization',
            'requestedPlan',
            'requestedBy',
            'invoice',
        ]);

        $seen = [];

        foreach ($this->platformRecipients() as $recipient) {
            $email = mb_strtolower(
                trim((string) $recipient->email)
            );

            if (
                ! filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
                || isset($seen[$email])
            ) {
                continue;
            }

            $seen[$email] = true;

            $this->createAndDispatch(
                event: $event,
                planRequest: $planRequest,
                recipientUserId: $recipient->id,
                recipientEmail: $email,
                version: $version
            );
        }
    }

    private function createAndDispatch(
        string $event,
        OrganizationSubscriptionPlanRequest $planRequest,
        ?int $recipientUserId,
        string $recipientEmail,
        string $version,
        ?SubscriptionTransaction $transaction = null
    ): void {
        $invoice = $planRequest->invoice;

        if ($invoice === null) {
            return;
        }

        $fingerprint = hash(
            'sha256',
            implode(
                '|',
                [
                    $event,
                    $planRequest->id,
                    $invoice->id,
                    $transaction?->id ?? 'none',
                    $version,
                    mb_strtolower($recipientEmail),
                ]
            )
        );

        [$subject, $message] = $this->copy(
            $event,
            $planRequest,
            $transaction
        );

        $notification =
            SubscriptionWorkflowNotification::query()
                ->firstOrCreate(
                    [
                        'fingerprint' => $fingerprint,
                    ],
                    [
                        'organization_id' =>
                            $planRequest->organization_id,
                        'subscription_invoice_id' =>
                            $invoice->id,
                        'organization_subscription_plan_request_id' =>
                            $planRequest->id,
                        'subscription_transaction_id' =>
                            $transaction?->id,
                        'recipient_user_id' =>
                            $recipientUserId,
                        'event' => $event,
                        'status' =>
                            SubscriptionWorkflowNotification::
                                STATUS_QUEUED,
                        'recipient_email' =>
                            $recipientEmail,
                        'subject' => $subject,
                        'message' => $message,
                        'metadata' => [
                            'organization_name' =>
                                $planRequest->organization?->name,
                            'invoice_number' =>
                                $invoice->invoice_number,
                            'plan_name' =>
                                $planRequest->requestedPlan?->name,
                            'billing_cycle' =>
                                $planRequest->billing_cycle,
                            'amount' =>
                                $planRequest->amount_snapshot,
                            'currency' =>
                                $planRequest->currency,
                            'payment_reference' =>
                                $planRequest->payment_claim_reference,
                        ],
                    ]
                );

        if (! $notification->wasRecentlyCreated) {
            return;
        }

        SendSubscriptionWorkflowNotificationJob::dispatch(
            (int) $notification->id
        )->afterCommit();
    }

    private function customerRecipient(
        OrganizationSubscriptionPlanRequest $planRequest
    ): ?array {
        $candidates = [
            $planRequest
                ->invoice
                ?->subscription
                ?->billingOwner,
            $planRequest->requestedBy,
        ];

        foreach ($candidates as $candidate) {
            if (
                $candidate === null
                || ! (bool) $candidate->is_active
            ) {
                continue;
            }

            $email = mb_strtolower(
                trim((string) $candidate->email)
            );

            if (
                filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                return [
                    'user_id' => $candidate->id,
                    'email' => $email,
                ];
            }
        }

        $organizationEmail = mb_strtolower(
            trim(
                (string) (
                    $planRequest->organization?->email
                    ?: $planRequest->organization?->support_email
                )
            )
        );

        if (
            filter_var(
                $organizationEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return [
                'user_id' => null,
                'email' => $organizationEmail,
            ];
        }

        return null;
    }

    private function platformRecipients(): Collection
    {
        return User::query()
            ->whereNull('organization_id')
            ->where('is_active', true)
            ->whereHas(
                'platformRole',
                function ($query): void {
                    $query->whereIn(
                        'slug',
                        [
                            'super-admin',
                            'billing',
                            'support',
                        ]
                    );
                }
            )
            ->orderBy('id')
            ->get();
    }

    private function copy(
        string $event,
        OrganizationSubscriptionPlanRequest $planRequest,
        ?SubscriptionTransaction $transaction
    ): array {
        $organization =
            $planRequest->organization?->name
            ?? 'Organization';

        $plan =
            $planRequest->requestedPlan?->name
            ?? 'subscription plan';

        $invoice =
            $planRequest->invoice?->invoice_number
            ?? 'invoice';

        return match ($event) {
            SubscriptionWorkflowNotification::
                EVENT_INVOICE_READY_CUSTOMER => [
                    "Subscription invoice {$invoice} is ready",
                    "Your {$plan} subscription invoice has been issued. Review the attached invoice and payment instructions, then report the payment from your account.",
                ],

            SubscriptionWorkflowNotification::
                EVENT_PLAN_SELECTED_PLATFORM => [
                    "New subscription selected by {$organization}",
                    "{$organization} selected the {$plan} plan on the {$planRequest->billing_cycle} billing cycle. Invoice {$invoice} was issued automatically.",
                ],

            SubscriptionWorkflowNotification::
                EVENT_PAYMENT_REPORTED_PLATFORM => [
                    "Payment reported by {$organization}",
                    "{$organization} reported payment for invoice {$invoice}. Reference: {$planRequest->payment_claim_reference}. Verify the payment before activating the plan.",
                ],

            SubscriptionWorkflowNotification::
                EVENT_REQUEST_CANCELLED_PLATFORM => [
                    "Subscription request cancelled by {$organization}",
                    "{$organization} cancelled its {$plan} subscription request and invoice {$invoice}.",
                ],

            SubscriptionWorkflowNotification::
                EVENT_PAYMENT_REJECTED_CUSTOMER => [
                    "Payment could not be verified for {$invoice}",
                    "Platform Billing could not verify the payment reported for invoice {$invoice}. Review the reason in your account, then report the payment again or cancel the request.",
                ],

            SubscriptionWorkflowNotification::
                EVENT_PAYMENT_RECEIPT_CUSTOMER => [
                    "Payment confirmed — receipt {$transaction?->reference}",
                    "Payment for invoice {$invoice} has been confirmed and the {$plan} subscription is active. Your receipt is attached.",
                ],

            default => [
                "Subscription update for {$organization}",
                'There is an update to the subscription workflow.',
            ],
        };
    }
}
