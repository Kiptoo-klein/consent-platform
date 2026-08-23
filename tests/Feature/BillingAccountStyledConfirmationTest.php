<?php

namespace Tests\Feature;

use Tests\TestCase;

class BillingAccountStyledConfirmationTest extends TestCase
{
    public function test_organization_invoice_cancel_uses_a_styled_confirmation(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/organization-billing/'
                .'invoice.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-billing-request-cancel-confirmation',
            'cancel-billing-request-{{ $workflowPlanRequest->id }}',
            'Cancel subscription request?',
            'confirm-text="Cancel request"',
            'variant="danger"',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $view
            );
        }

        $this->assertStringNotContainsString(
            "onsubmit=\"return confirm('Cancel this subscription request",
            $view
        );
    }

    public function test_platform_payment_confirmation_uses_the_styled_modal(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/platform/subscription-invoices/'
                .'show.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-payment-activation-confirmation',
            'confirm-payment-{{ $invoice->id }}',
            'Confirm payment and activate plan?',
            'confirm-text="Confirm and activate"',
            'variant="success"',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $view
            );
        }

        $this->assertStringNotContainsString(
            "onsubmit=\"return confirm('Confirm this payment",
            $view
        );
    }

    public function test_subscription_cancel_locations_use_styled_confirmations(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/organization-subscription/'
                .'plans.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-subscription-summary-cancel-confirmation',
            'data-subscription-card-cancel-confirmation',
            'cancel-subscription-summary-{{ $pendingRequest->id }}',
            'cancel-subscription-card-{{ $pendingRequest->id }}-{{ $plan->id }}',
            'Cancel pending plan change?',
            '<x-action-confirmation-modal',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $view
            );
        }

        $this->assertStringNotContainsString(
            'onsubmit="return confirm',
            $view
        );
    }

    public function test_profile_archive_validates_password_before_opening_modal(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/profile/edit.blade.php'
            )
        );

        $this->assertIsString($view);

        foreach ([
            'data-profile-archive-confirmation',
            'archive-profile-{{ auth()->id() }}',
            'x-ref="archivePassword"',
            '.reportValidity()',
            'Archive your account?',
            ':confirm-text="',
            "'Archive my account'",
            "'Archive organization'",
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $view
            );
        }

        $this->assertStringNotContainsString(
            "onsubmit=\"return confirm('Archive your account?",
            $view
        );
    }
}
