<?php

namespace Tests;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Give a test organization paid access to protected workflows.
     */
    protected function enablePaidOrganizationAccess(
        Organization $organization,
        User $billingOwner
    ): void {
        $this->seed(SubscriptionPlanSeeder::class);

        $plan = SubscriptionPlan::query()
            ->where('slug', 'basic')
            ->firstOrFail();

        $organization->subscription()->updateOrCreate(
            [],
            [
                'subscription_plan_id' => $plan->id,
                'billing_owner_user_id' => $billingOwner->id,
                'status' => OrganizationSubscriptionStatus::ACTIVE,
                'payment_status' => SubscriptionPaymentStatus::PAID,
                'requires_plan_selection' => false,
                'plan_selected_at' => now(),
                'starts_at' => now(),
            ]
        );
    }

    /**
     * Mark the currently authenticated fixture user as email verified.
     *
     * Use this only when a test is about another application concern
     * such as billing, evaluation limits, permissions, or subscriptions.
     * Verification-specific tests must exercise the real unverified flow.
     */
    protected function verifyAuthenticatedUser(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new \LogicException(
                'Expected an authenticated user before verification.'
            );
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        return $user->refresh();
    }

}
