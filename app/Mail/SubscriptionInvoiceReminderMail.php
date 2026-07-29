<?php

namespace App\Mail;

use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionInvoiceReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public SubscriptionInvoice $invoice,
        public string $reminderKey
    ) {
        $this->invoice->loadMissing([
            'organization',
            'plan',
            'subscription.billingOwner',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine()
        );
    }

    public function content(): Content
    {
        return new Content(
            view:
                'emails.subscription-invoice-reminder',

            with: [
                'headline' =>
                    $this->headline(),

                'introMessage' =>
                    $this->introMessage(),

                'invoiceUrl' =>
                    route(
                        'organization-billing.invoices.show',
                        $this->invoice
                    ),
            ]
        );
    }

    public function subjectLine(): string
    {
        $invoiceNumber =
            $this->invoice->invoice_number;

        return match ($this->reminderKey) {
            SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS =>
                "Invoice {$invoiceNumber} is due in 3 days",

            SubscriptionInvoiceNotification::REMINDER_DUE_IN_1_DAY =>
                "Invoice {$invoiceNumber} is due tomorrow",

            SubscriptionInvoiceNotification::REMINDER_OVERDUE_1_DAY =>
                "Invoice {$invoiceNumber} is 1 day overdue",

            SubscriptionInvoiceNotification::REMINDER_OVERDUE_7_DAYS =>
                "Invoice {$invoiceNumber} is 7 days overdue",

            default =>
                "Payment reminder for invoice {$invoiceNumber}",
        };
    }

    public function headline(): string
    {
        return match ($this->reminderKey) {
            SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS =>
                'Your subscription invoice is due in 3 days',

            SubscriptionInvoiceNotification::REMINDER_DUE_IN_1_DAY =>
                'Your subscription invoice is due tomorrow',

            SubscriptionInvoiceNotification::REMINDER_OVERDUE_1_DAY =>
                'Your subscription invoice is overdue',

            SubscriptionInvoiceNotification::REMINDER_OVERDUE_7_DAYS =>
                'Your subscription invoice remains overdue',

            default =>
                'Subscription invoice payment reminder',
        };
    }

    public function introMessage(): string
    {
        $organizationName =
            $this->invoice->organization?->name
            ?? 'your organization';

        return match ($this->reminderKey) {
            SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS =>
                "This is a reminder that {$organizationName}'s subscription invoice is due in 3 days.",

            SubscriptionInvoiceNotification::REMINDER_DUE_IN_1_DAY =>
                "This is a reminder that {$organizationName}'s subscription invoice is due tomorrow.",

            SubscriptionInvoiceNotification::REMINDER_OVERDUE_1_DAY =>
                "{$organizationName}'s subscription invoice became overdue yesterday.",

            SubscriptionInvoiceNotification::REMINDER_OVERDUE_7_DAYS =>
                "{$organizationName}'s subscription invoice has remained overdue for 7 days.",

            default =>
                "{$organizationName} has an outstanding subscription invoice.",
        };
    }
}
