<?php

namespace Tests\Feature;

use App\Http\Controllers\AutomaticOrganizationSubscriptionPlanController;
use App\Http\Controllers\OrganizationSubscriptionPaymentClaimController;
use App\Http\Controllers\Platform\SubscriptionPaymentClaimController;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SubscriptionWorkflowNotification;
use App\Services\AutomaticSubscriptionWorkflowService;
use App\Services\SubscriptionWorkflowNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutomaticSubscriptionPaymentClaimWorkflowTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_schema_and_routes_are_available(): void
    {
        foreach (
            [
                'payment_claim_status',
                'payment_claim_amount',
                'payment_claim_currency',
                'payment_claim_method',
                'payment_claim_reference',
                'payment_claim_paid_at',
                'payment_claim_notes',
                'payment_claim_submitted_at',
                'payment_claim_submitted_by_user_id',
                'payment_claim_reviewed_at',
                'payment_claim_reviewed_by_user_id',
                'payment_claim_rejection_reason',
            ] as $column
        ) {
            $this->assertTrue(
                Schema::hasColumn(
                    'organization_subscription_plan_requests',
                    $column
                ),
                "Missing payment-claim column: {$column}"
            );
        }

        $this->assertTrue(
            Schema::hasTable(
                'subscription_workflow_notifications'
            )
        );

        foreach (
            [
                'organization-subscription-plans.select-and-issue',
                'organization-subscription-payment-claims.store',
                'organization-subscription-payment-claims.cancel',
                'platform.organizations.subscription-invoices.payment-claim.confirm',
                'platform.organizations.subscription-invoices.payment-claim.reject',
            ] as $routeName
        ) {
            $this->assertNotNull(
                app('router')
                    ->getRoutes()
                    ->getByName($routeName),
                "Missing workflow route: {$routeName}"
            );
        }
    }

    public function test_payment_claim_state_helpers_lock_and_unlock_actions(): void
    {
        $planRequest = new OrganizationSubscriptionPlanRequest([
            'status' =>
                OrganizationSubscriptionPlanRequest::
                    STATUS_PENDING,
            'payment_claim_status' => null,
        ]);

        $this->assertTrue(
            $planRequest->mayReportPayment()
        );

        $planRequest->payment_claim_status =
            OrganizationSubscriptionPlanRequest::
                PAYMENT_CLAIM_PENDING;

        $this->assertTrue(
            $planRequest->hasPendingPaymentClaim()
        );

        $this->assertFalse(
            $planRequest->mayReportPayment()
        );

        $planRequest->payment_claim_status =
            OrganizationSubscriptionPlanRequest::
                PAYMENT_CLAIM_REJECTED;

        $this->assertFalse(
            $planRequest->hasPendingPaymentClaim()
        );

        $this->assertTrue(
            $planRequest->mayReportPayment()
        );

        $planRequest->payment_claim_status =
            OrganizationSubscriptionPlanRequest::
                PAYMENT_CLAIM_CONFIRMED;

        $this->assertTrue(
            $planRequest->hasConfirmedPaymentClaim()
        );

        $this->assertFalse(
            $planRequest->mayReportPayment()
        );
    }

    public function test_workflow_services_and_controllers_resolve_from_container(): void
    {
        $this->assertInstanceOf(
            AutomaticSubscriptionWorkflowService::class,
            app(AutomaticSubscriptionWorkflowService::class)
        );

        $this->assertInstanceOf(
            SubscriptionWorkflowNotificationService::class,
            app(SubscriptionWorkflowNotificationService::class)
        );

        $this->assertInstanceOf(
            AutomaticOrganizationSubscriptionPlanController::class,
            app(AutomaticOrganizationSubscriptionPlanController::class)
        );

        $this->assertInstanceOf(
            OrganizationSubscriptionPaymentClaimController::class,
            app(OrganizationSubscriptionPaymentClaimController::class)
        );

        $this->assertInstanceOf(
            SubscriptionPaymentClaimController::class,
            app(SubscriptionPaymentClaimController::class)
        );

        $this->assertSame(
            'invoice_ready_customer',
            SubscriptionWorkflowNotification::
                EVENT_INVOICE_READY_CUSTOMER
        );
    }

    public function test_payment_claim_form_uses_invoice_payment_method_dropdown(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/organization-billing/invoice.blade.php'
                )
            );

        $controller =
            file_get_contents(
                app_path(
                    'Http/Controllers/OrganizationSubscriptionPaymentClaimController.php'
                )
            );

        $this->assertIsString(
            $view
        );

        $this->assertIsString(
            $controller
        );

        $this->assertStringContainsString(
            '<select',
            $view
        );

        $this->assertStringContainsString(
            'name="payment_method"',
            $view
        );

        $this->assertStringContainsString(
            'Select payment method',
            $view
        );

        $this->assertStringContainsString(
            "'M-Pesa'",
            $view
        );

        $this->assertStringContainsString(
            "'Bank Transfer'",
            $view
        );

        $this->assertStringNotContainsString(
            'placeholder="M-Pesa, bank transfer..."',
            $view
        );

        $this->assertStringContainsString(
            'Rule::in(',
            $controller
        );

        $this->assertStringContainsString(
            'Select one of the payment methods available on this invoice.',
            $controller
        );
    }



    public function test_invoice_cancel_request_panel_is_clear_and_distinct(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/organization-billing/invoice.blade.php'
                )
            );

        $this->assertIsString(
            $view
        );

        $this->assertStringContainsString(
            'data-invoice-cancel-request-panel',
            $view
        );

        $this->assertStringContainsString(
            'Cancel this plan change',
            $view
        );

        $this->assertStringContainsString(
            'unpaid invoice will be cancelled',
            $view
        );

        $this->assertStringContainsString(
            'current subscription will remain active.',
            $view
        );

        $this->assertStringContainsString(
            'Cancel Request',
            $view
        );

        $this->assertStringContainsString(
            'Available until payment is reported.',
            $view
        );
    }

    public function test_invoice_cancel_panel_is_stacked_below_payment_form(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/organization-billing/invoice.blade.php'
                )
            );

        $this->assertIsString(
            $view
        );

        $this->assertStringContainsString(
            'data-invoice-cancel-request-panel',
            $view
        );

        $this->assertStringNotContainsString(
            'lg:grid-cols-[1fr_310px]',
            $view
        );

        $this->assertStringNotContainsString(
            'lg:border-r lg:border-slate-200',
            $view
        );

        $this->assertStringNotContainsString(
            'lg:border-l lg:border-t-0',
            $view
        );
    }



    public function test_invoice_cancel_panel_uses_compact_horizontal_warning_layout(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/organization-billing/invoice.blade.php'
                )
            );

        $this->assertIsString(
            $view
        );

        $this->assertStringContainsString(
            'data-invoice-cancel-request-panel',
            $view
        );

        $this->assertStringContainsString(
            'lg:flex-row lg:items-center lg:justify-between',
            $view
        );

        $this->assertStringContainsString(
            'background-color:#f8fafc',
            $view
        );

        $this->assertStringContainsString(
            'border-color:#dc2626',
            $view
        );

        $this->assertStringContainsString(
            'current subscription will remain active.',
            $view
        );
    }

    public function test_invoice_cancel_panel_does_not_use_global_aside_element(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/organization-billing/invoice.blade.php'
                )
            );

        $this->assertIsString(
            $view
        );

        $panelPosition =
            strpos(
                $view,
                'data-invoice-cancel-request-panel'
            );

        $this->assertNotFalse(
            $panelPosition
        );

        $panelStart =
            strrpos(
                substr(
                    $view,
                    0,
                    $panelPosition
                ),
                '<div'
            );

        $this->assertNotFalse(
            $panelStart
        );

        $this->assertStringNotContainsString(
            '<aside',
            substr(
                $view,
                $panelStart,
                $panelPosition - $panelStart
            )
        );
    }



    public function test_invoice_cancel_panel_uses_secondary_action_styling(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/organization-billing/invoice.blade.php'
                )
            );

        $this->assertIsString(
            $view
        );

        $this->assertStringContainsString(
            'data-invoice-cancel-request-panel',
            $view
        );

        $this->assertStringContainsString(
            'background-color:#f8fafc',
            $view
        );

        $this->assertStringContainsString(
            'border-color:#dc2626',
            $view
        );

        $this->assertStringContainsString(
            'Cancel this plan change',
            $view
        );

        $this->assertStringContainsString(
            'background-color:#b91c1c !important;color:#ffffff',
            $view
        );

        $this->assertStringNotContainsString(
            'background-color:#fff7f7',
            $view
        );
    }



    public function test_invoice_cancel_button_is_solid_red_with_white_text(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/organization-billing/invoice.blade.php'
                )
            );

        $this->assertIsString(
            $view
        );

        $panelPosition =
            strpos(
                $view,
                'data-invoice-cancel-request-panel'
            );

        $buttonPosition =
            strpos(
                $view,
                'Cancel Request',
                $panelPosition
            );

        $this->assertNotFalse(
            $panelPosition
        );

        $this->assertNotFalse(
            $buttonPosition
        );

        $buttonStart =
            strrpos(
                substr(
                    $view,
                    0,
                    $buttonPosition
                ),
                '<button'
            );

        $buttonEnd =
            strpos(
                $view,
                '</button>',
                $buttonPosition
            );

        $this->assertNotFalse(
            $buttonStart
        );

        $this->assertNotFalse(
            $buttonEnd
        );

        $button =
            substr(
                $view,
                $buttonStart,
                $buttonEnd
                    + strlen('</button>')
                    - $buttonStart
            );

        $this->assertStringContainsString(
            'background-color:#b91c1c !important',
            $button
        );

        $this->assertStringContainsString(
            'color:#ffffff !important',
            $button
        );

        $this->assertStringContainsString(
            'border-color:#991b1b !important',
            $button
        );
    }



    public function test_other_plan_cards_use_quiet_pending_request_state(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/organization-subscription/plans.blade.php'
                )
            );

        $this->assertIsString(
            $view
        );

        $this->assertStringContainsString(
            'Cancel pending change',
            $view
        );

        $this->assertStringContainsString(
            'Pending request active',
            $view
        );

        $this->assertStringContainsString(
            'Cancel it above before choosing this plan.',
            $view
        );

        $this->assertStringNotContainsString(
            'Cancel current request to choose this plan',
            $view
        );
    }



    public function test_subscription_plan_pending_states_use_clear_status_colors(): void
    {
        $view =
            file_get_contents(
                resource_path(
                    'views/organization-subscription/plans.blade.php'
                )
            );

        $this->assertIsString(
            $view
        );

        $this->assertStringContainsString(
            'background-color:#f59e0b !important;color:#ffffff',
            $view
        );

        $this->assertStringContainsString(
            'background-color:#dc2626 !important;color:#ffffff',
            $view
        );

        $this->assertStringContainsString(
            'background-color:#fffbeb !important;border-color:#f59e0b',
            $view
        );

        $this->assertStringContainsString(
            'color:#92400e !important',
            $view
        );
    }


}
