<?php

namespace App\Http\Controllers\Platform;

use App\Enums\SubscriptionInvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SubscriptionInvoice;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionInvoiceController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    /**
     * Display invoices belonging to an organization.
     */
    public function index(
        Organization $organization
    ): View {
        $subscription = $organization
            ->subscription()
            ->with('plan')
            ->firstOrFail();

        $invoices = SubscriptionInvoice::query()
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
                'issuedBy',
            ])
            ->latest('id')
            ->paginate(20);

        return view(
            'platform.subscription-invoices.index',
            [
                'organization' =>
                    $organization,

                'subscription' =>
                    $subscription,

                'invoices' =>
                    $invoices,
            ]
        );
    }

    /**
     * Create a draft subscription invoice.
     */
    public function store(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $request->merge([
            'invoice_number' =>
                trim((string) $request->input('invoice_number')),

            'currency' =>
                strtoupper(
                    trim((string) $request->input('currency'))
                ),
        ]);

        $validated = $request->validate([
            'invoice_number' => [
                'required',
                'string',
                'max:120',
                Rule::unique(
                    'subscription_invoices',
                    'invoice_number'
                ),
            ],

            'subtotal' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'tax_amount' => [
                'required',
                'numeric',
                'gte:0',
                'decimal:0,2',
            ],

            'currency' => [
                'required',
                'string',
                'regex:/^[A-Z]{3}$/',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $subtotal = number_format(
            (float) $validated['subtotal'],
            2,
            '.',
            ''
        );

        $taxAmount = number_format(
            (float) $validated['tax_amount'],
            2,
            '.',
            ''
        );

        $totalAmount = number_format(
            round(
                (float) $subtotal
                + (float) $taxAmount,
                2
            ),
            2,
            '.',
            ''
        );

        DB::transaction(function () use (
            $request,
            $organization,
            $validated,
            $subtotal,
            $taxAmount,
            $totalAmount
        ): void {
            $subscription = $organization
                ->subscription()
                ->lockForUpdate()
                ->firstOrFail();

            $invoice =
                SubscriptionInvoice::query()->create([
                    'organization_subscription_id' =>
                        $subscription->id,

                    'organization_id' =>
                        $organization->id,

                    'subscription_plan_id' =>
                        $subscription->subscription_plan_id,

                    'invoice_number' =>
                        $validated['invoice_number'],

                    'status' =>
                        SubscriptionInvoiceStatus::DRAFT,

                    'issue_date' => null,
                    'due_date' => null,

                    'subtotal' =>
                        $subtotal,

                    'tax_amount' =>
                        $taxAmount,

                    'total_amount' =>
                        $totalAmount,

                    'currency' =>
                        $validated['currency'],

                    'notes' =>
                        filled($validated['notes'] ?? null)
                            ? trim($validated['notes'])
                            : null,

                    'issued_by_user_id' => null,
                    'paid_at' => null,
                    'voided_at' => null,
                    'cancelled_at' => null,
                ]);

            $invoice->refresh();

            $this->activityLogger->log(
                action:
                    'organization.subscription_invoice_created',

                description:
                    'Draft subscription invoice created.',

                subject:
                    $invoice,

                organizationId:
                    $organization->id,

                properties: [
                    'invoice_number' =>
                        $invoice->invoice_number,

                    'status' =>
                        $invoice->status?->value,

                    'subtotal' =>
                        $invoice->subtotal,

                    'tax_amount' =>
                        $invoice->tax_amount,

                    'total_amount' =>
                        $invoice->total_amount,

                    'currency' =>
                        $invoice->currency,

                    'plan_id' =>
                        $invoice->subscription_plan_id,

                    'created_by_user_id' =>
                        $request->user()->id,
                ],
            );
        });

        return redirect()
            ->route(
                'platform.organizations.subscription-invoices.index',
                $organization
            )
            ->with(
                'success',
                'Draft subscription invoice created successfully.'
            );
    }

    /**
     * Display one subscription invoice.
     */
    public function show(
        Organization $organization,
        SubscriptionInvoice $subscriptionInvoice
    ): View {
        $this->ensureInvoiceBelongsToOrganization(
            $subscriptionInvoice,
            $organization
        );

        $subscriptionInvoice->load([
            'organization',
            'subscription',
            'plan',
            'issuedBy',

            'transactions' => function ($query): void {
                $query->latest('id');
            },

            'reminderNotifications' => function ($query): void {
                $query
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(20);
            },
        ]);

        return view(
            'platform.subscription-invoices.show',
            [
                'organization' =>
                    $organization,

                'invoice' =>
                    $subscriptionInvoice,
            ]
        );
    }

    /**
     * Issue a draft invoice.
     */
    public function issue(
        Request $request,
        Organization $organization,
        SubscriptionInvoice $subscriptionInvoice
    ): RedirectResponse {
        $this->ensureInvoiceBelongsToOrganization(
            $subscriptionInvoice,
            $organization
        );

        $validated = $request->validate([
            'issue_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'due_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:issue_date',
            ],
        ]);

        DB::transaction(function () use (
            $request,
            $organization,
            $subscriptionInvoice,
            $validated
        ): void {
            $invoice = SubscriptionInvoice::query()
                ->whereKey(
                    $subscriptionInvoice->id
                )
                ->where(
                    'organization_id',
                    $organization->id
                )
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $invoice->status
                    === SubscriptionInvoiceStatus::DRAFT,
                422,
                'Only draft invoices can be issued.'
            );

            $invoice->update([
                'status' =>
                    SubscriptionInvoiceStatus::ISSUED,

                'issue_date' =>
                    $validated['issue_date'],

                'due_date' =>
                    $validated['due_date'],

                'issued_by_user_id' =>
                    $request->user()->id,
            ]);

            $invoice->refresh();

            $this->activityLogger->log(
                action:
                    'organization.subscription_invoice_issued',

                description:
                    'Subscription invoice issued.',

                subject:
                    $invoice,

                organizationId:
                    $organization->id,

                properties: [
                    'invoice_number' =>
                        $invoice->invoice_number,

                    'status' =>
                        $invoice->status?->value,

                    'issue_date' =>
                        $invoice->issue_date
                            ?->toDateString(),

                    'due_date' =>
                        $invoice->due_date
                            ?->toDateString(),

                    'total_amount' =>
                        $invoice->total_amount,

                    'currency' =>
                        $invoice->currency,

                    'issued_by_user_id' =>
                        $invoice->issued_by_user_id,
                ],
            );
        });

        return redirect()
            ->route(
                'platform.organizations.subscription-invoices.show',
                [
                    $organization,
                    $subscriptionInvoice,
                ]
            )
            ->with(
                'success',
                'Subscription invoice issued successfully.'
            );
    }

    /**
     * Prevent cross-organization invoice access.
     */
    private function ensureInvoiceBelongsToOrganization(
        SubscriptionInvoice $invoice,
        Organization $organization
    ): void {
        abort_unless(
            (int) $invoice->organization_id
                === (int) $organization->id,
            404
        );
    }
}
