<?php

namespace App\Services;

use App\Enums\SubscriptionInvoiceStatus;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SubscriptionInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class AutomaticSubscriptionWorkflowService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly SubscriptionPaymentSettingsService
            $paymentSettingsService
    ) {
    }

    public function issueInvoice(
        OrganizationSubscriptionPlanRequest $planRequest,
        ?User $actor
    ): SubscriptionInvoice {
        $invoice = SubscriptionInvoice::query()
            ->whereKey(
                $planRequest->subscription_invoice_id
            )
            ->where(
                'organization_id',
                $planRequest->organization_id
            )
            ->lockForUpdate()
            ->firstOrFail();

        if (
            $invoice->status
            === SubscriptionInvoiceStatus::DRAFT
        ) {
            $issueDate = now()->startOfDay();

            $invoice->forceFill([
                'status' =>
                    SubscriptionInvoiceStatus::ISSUED,

                'issue_date' =>
                    $issueDate->toDateString(),

                'due_date' =>
                    $issueDate
                        ->copy()
                        ->addDays(7)
                        ->toDateString(),

                'payment_details_snapshot' =>
                    $this->paymentDetailsSnapshot(),

                'issued_by_user_id' => null,
            ])->save();

            $this->activityLogger->log(
                action:
                    'organization.subscription_invoice_automatically_issued',

                description:
                    'The system automatically issued a subscription invoice after the organization selected a published plan.',

                subject: $invoice,

                organizationId:
                    $planRequest->organization_id,

                properties: [
                    'subscription_plan_request_id' =>
                        $planRequest->id,

                    'invoice_number' =>
                        $invoice->invoice_number,

                    'requested_by_user_id' =>
                        $actor?->id,

                    'issue_date' =>
                        $invoice->issue_date?->toDateString(),

                    'due_date' =>
                        $invoice->due_date?->toDateString(),
                ],
            );
        }

        return $invoice->refresh();
    }

    private function paymentDetailsSnapshot(): array
    {
        $preferredMethods = [
            'invoiceSnapshot',
            'snapshotForInvoice',
            'paymentDetailsSnapshot',
            'snapshot',
            'currentSnapshot',
            'resolvedSettings',
            'settings',
        ];

        foreach ($preferredMethods as $method) {
            $value = $this->invokeZeroArgumentMethod(
                $method
            );

            if ($value !== null) {
                return $this->normaliseSnapshot(
                    $value
                );
            }
        }

        $reflection = new ReflectionClass(
            $this->paymentSettingsService
        );

        foreach (
            $reflection->getMethods(
                ReflectionMethod::IS_PUBLIC
            ) as $method
        ) {
            if (
                $method->isStatic()
                || $method->getNumberOfRequiredParameters() > 0
                || ! preg_match(
                    '/snapshot|payment.*detail|setting/i',
                    $method->getName()
                )
            ) {
                continue;
            }

            try {
                return $this->normaliseSnapshot(
                    $method->invoke(
                        $this->paymentSettingsService
                    )
                );
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return [];
    }

    private function invokeZeroArgumentMethod(
        string $method
    ): mixed {
        if (
            ! method_exists(
                $this->paymentSettingsService,
                $method
            )
        ) {
            return null;
        }

        $reflection = new ReflectionMethod(
            $this->paymentSettingsService,
            $method
        );

        if (
            ! $reflection->isPublic()
            || $reflection->getNumberOfRequiredParameters() > 0
        ) {
            return null;
        }

        try {
            return $reflection->invoke(
                $this->paymentSettingsService
            );
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function normaliseSnapshot(
        mixed $value
    ): array {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Collection) {
            return $value->all();
        }

        if ($value instanceof Model) {
            return $value->toArray();
        }

        if (
            is_object($value)
            && method_exists($value, 'toArray')
        ) {
            return (array) $value->toArray();
        }

        return [];
    }
}
