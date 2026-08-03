<?php

namespace App\Mail;

use App\Models\SubscriptionWorkflowNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SubscriptionWorkflowMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public SubscriptionWorkflowNotification $notification
    ) {
        $this->notification->loadMissing([
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
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notification->subject
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-workflow',
            with: [
                'actionUrl' => $this->actionUrl(),
                'actionLabel' => $this->actionLabel(),
            ]
        );
    }

    public function attachments(): array
    {
        if (
            $this->notification->event
            === SubscriptionWorkflowNotification::
                EVENT_INVOICE_READY_CUSTOMER
        ) {
            $invoice = $this->notification->invoice;

            if ($invoice === null) {
                return [];
            }

            $number = Str::slug(
                (string) $invoice->invoice_number
            );

            return [
                Attachment::fromData(
                    fn (): string =>
                        Pdf::loadView(
                            'pdfs.subscription-invoice',
                            [
                                'invoice' => $invoice,
                                'subscriptionInvoice' => $invoice,
                            ]
                        )
                            ->setPaper('a4', 'portrait')
                            ->output(),
                    "subscription_invoice_{$number}.pdf"
                )->withMime('application/pdf'),
            ];
        }

        if (
            $this->notification->event
            === SubscriptionWorkflowNotification::
                EVENT_PAYMENT_RECEIPT_CUSTOMER
        ) {
            $transaction = $this->notification->transaction;

            if ($transaction === null) {
                return [];
            }

            $reference = Str::slug(
                (string) $transaction->reference
            );

            return [
                Attachment::fromData(
                    fn (): string =>
                        Pdf::loadView(
                            'pdfs.subscription-receipt',
                            [
                                'transaction' => $transaction,
                                'subscriptionTransaction' => $transaction,
                            ]
                        )
                            ->setPaper('a4', 'portrait')
                            ->output(),
                    "subscription_receipt_{$reference}.pdf"
                )->withMime('application/pdf'),
            ];
        }

        return [];
    }

    private function actionUrl(): ?string
    {
        return match ($this->notification->event) {
            SubscriptionWorkflowNotification::
                EVENT_INVOICE_READY_CUSTOMER,
            SubscriptionWorkflowNotification::
                EVENT_PAYMENT_REJECTED_CUSTOMER =>
                route(
                    'organization-billing.invoices.show',
                    $this->notification->invoice
                ),

            SubscriptionWorkflowNotification::
                EVENT_PAYMENT_RECEIPT_CUSTOMER =>
                $this->notification->transaction
                    ? route(
                        'organization-billing.receipts.show',
                        $this->notification->transaction
                    )
                    : null,

            SubscriptionWorkflowNotification::
                EVENT_PLAN_SELECTED_PLATFORM,
            SubscriptionWorkflowNotification::
                EVENT_PAYMENT_REPORTED_PLATFORM,
            SubscriptionWorkflowNotification::
                EVENT_REQUEST_CANCELLED_PLATFORM =>
                route(
                    'platform.organizations.show',
                    $this->notification->organization
                ),

            default => null,
        };
    }

    private function actionLabel(): ?string
    {
        return match ($this->notification->event) {
            SubscriptionWorkflowNotification::
                EVENT_INVOICE_READY_CUSTOMER =>
                'Open Invoice',

            SubscriptionWorkflowNotification::
                EVENT_PAYMENT_REJECTED_CUSTOMER =>
                'Review Payment Request',

            SubscriptionWorkflowNotification::
                EVENT_PAYMENT_RECEIPT_CUSTOMER =>
                'Open Receipt',

            SubscriptionWorkflowNotification::
                EVENT_PLAN_SELECTED_PLATFORM,
            SubscriptionWorkflowNotification::
                EVENT_PAYMENT_REPORTED_PLATFORM,
            SubscriptionWorkflowNotification::
                EVENT_REQUEST_CANCELLED_PLATFORM =>
                'Open Organization',

            default => null,
        };
    }
}
