<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionTransaction;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OrganizationBillingController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    /**
     * Display read-only subscription billing history.
     */
    public function index(Request $request): View
    {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        $transactions = SubscriptionTransaction::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->where(
                'organization_subscription_id',
                $subscription->id
            )
            ->with([
                'plan',
                'recordedBy',
            ])
            ->latest('id')
            ->paginate(20);

        return view(
            'organization-billing.index',
            [
                'organization' =>
                    $organization,

                'subscription' =>
                    $subscription,

                'transactions' =>
                    $transactions,
            ]
        );
    }

    /**
     * Display an HTML receipt belonging to the billing owner's organization.
     */
    public function show(
        Request $request,
        SubscriptionTransaction $subscriptionTransaction
    ): View {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        $this->ensureTransactionBelongsToSubscription(
            $subscriptionTransaction,
            $organization,
            $subscription
        );

        $subscriptionTransaction->load([
            'organization',
            'subscription',
            'plan',
            'recordedBy',
        ]);

        $this->activityLogger->log(
            action:
                'organization.subscription_receipt_viewed',

            description:
                'An Organization Admin viewed a subscription receipt.',

            subject:
                $subscriptionTransaction,

            organizationId:
                $organization->id,

            properties: [
                'reference' =>
                    $subscriptionTransaction->reference,

                'amount' =>
                    $subscriptionTransaction->amount,

                'currency' =>
                    $subscriptionTransaction->currency,
            ],
        );

        return view(
            'organization-billing.show',
            [
                'organization' =>
                    $organization,

                'transaction' =>
                    $subscriptionTransaction,
            ]
        );
    }

    /**
     * Generate and download a subscription receipt as a PDF.
     */
    public function download(
        Request $request,
        SubscriptionTransaction $subscriptionTransaction
    ): Response {
        [
            $organization,
            $subscription,
        ] = $this->billingContext($request);

        $this->ensureTransactionBelongsToSubscription(
            $subscriptionTransaction,
            $organization,
            $subscription
        );

        $subscriptionTransaction->load([
            'organization',
            'subscription',
            'plan',
            'recordedBy',
        ]);

        $filename = $this->downloadFilename(
            $subscriptionTransaction
        );

        $this->activityLogger->log(
            action:
                'organization.subscription_receipt_downloaded',

            description:
                'An Organization Admin downloaded a subscription receipt.',

            subject:
                $subscriptionTransaction,

            organizationId:
                $organization->id,

            properties: [
                'reference' =>
                    $subscriptionTransaction->reference,

                'amount' =>
                    $subscriptionTransaction->amount,

                'currency' =>
                    $subscriptionTransaction->currency,

                'download_filename' =>
                    $filename,
            ],
        );

        $pdf = Pdf::loadView(
            'pdfs.subscription-receipt',
            [
                'transaction' =>
                    $subscriptionTransaction,
            ]
        );

        $pdf->setPaper(
            'a4',
            'portrait'
        );

        return $pdf->download($filename);
    }

    /**
     * Resolve and authorize the current organization's billing owner.
     *
     * @return array{
     *     0: Organization,
     *     1: OrganizationSubscription
     * }
     */
    private function billingContext(
        Request $request
    ): array {
        $user = $request->user();

        $organization = $user
            ->organization()
            ->with([
                'subscription.plan',
            ])
            ->firstOrFail();

        $subscription = $organization->subscription;

        abort_unless(
            $subscription !== null
            && (int) $subscription->billing_owner_user_id
                === (int) $user->id,
            403
        );

        return [
            $organization,
            $subscription,
        ];
    }

    /**
     * Prevent receipt access across organization boundaries.
     */
    private function ensureTransactionBelongsToSubscription(
        SubscriptionTransaction $transaction,
        Organization $organization,
        OrganizationSubscription $subscription
    ): void {
        abort_unless(
            (int) $transaction->organization_id
                === (int) $organization->id
            && (int) $transaction->organization_subscription_id
                === (int) $subscription->id,
            403
        );
    }

    /**
     * Return a safe receipt filename based on the transaction reference.
     */
    private function downloadFilename(
        SubscriptionTransaction $transaction
    ): string {
        $safeReference = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '_',
            $transaction->reference
        );

        $safeReference = trim(
            (string) $safeReference,
            '_'
        );

        if ($safeReference === '') {
            $safeReference = 'transaction';
        }

        return Str::limit(
            "subscription_receipt_{$safeReference}",
            180,
            ''
        ).'.pdf';
    }
}
