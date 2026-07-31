<?php

namespace Tests\Unit;

use App\Models\OrganizationSubscriptionPlanRequest;
use Tests\TestCase;

class OrganizationSubscriptionPlanRequestTest extends TestCase
{
    public function test_unresolved_pending_request_can_be_cancelled_by_organization(): void
    {
        $planRequest =
            new OrganizationSubscriptionPlanRequest([
                'status' =>
                    OrganizationSubscriptionPlanRequest::
                        STATUS_PENDING,

                'resolved_at' =>
                    null,
            ]);

        $this->assertTrue(
            $planRequest->isPending()
        );

        $this->assertTrue(
            $planRequest
                ->canBeCancelledByOrganization()
        );
    }

    public function test_approved_request_cannot_be_cancelled_by_organization(): void
    {
        $planRequest =
            new OrganizationSubscriptionPlanRequest([
                'status' =>
                    OrganizationSubscriptionPlanRequest::
                        STATUS_APPROVED,

                'resolved_at' =>
                    now(),
            ]);

        $this->assertFalse(
            $planRequest->isPending()
        );

        $this->assertFalse(
            $planRequest
                ->canBeCancelledByOrganization()
        );
    }

    public function test_cancelled_request_cannot_be_cancelled_again(): void
    {
        $planRequest =
            new OrganizationSubscriptionPlanRequest([
                'status' =>
                    OrganizationSubscriptionPlanRequest::
                        STATUS_CANCELLED,

                'resolved_at' =>
                    now(),
            ]);

        $this->assertFalse(
            $planRequest
                ->canBeCancelledByOrganization()
        );
    }

    public function test_resolved_pending_request_is_not_directly_cancellable(): void
    {
        $planRequest =
            new OrganizationSubscriptionPlanRequest([
                'status' =>
                    OrganizationSubscriptionPlanRequest::
                        STATUS_PENDING,

                'resolved_at' =>
                    now(),
            ]);

        $this->assertTrue(
            $planRequest->isPending()
        );

        $this->assertFalse(
            $planRequest
                ->canBeCancelledByOrganization()
        );
    }
}
