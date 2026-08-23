<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SupportRequestSubmitted;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class SupportRequestController extends Controller
{
    public function __construct(
        protected ActivityLogger $activityLogger
    ) {
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $validated =
            $request->validateWithBag(
                'supportRequest',
                [
                    'support_type' => [
                        'required',
                        'string',
                        'in:question,problem',
                    ],

                    'support_message' => [
                        'required',
                        'string',
                        'min:10',
                        'max:5000',
                    ],

                    'support_context_route' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],

                    'support_context_path' => [
                        'nullable',
                        'string',
                        'max:1000',
                    ],

                    'support_help_context' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],
                ]
            );

        $user =
            $request->user();

        abort_unless(
            $user !== null,
            401
        );

        $user->loadMissing(
            'organization'
        );

        $organization =
            $user->organization;

        abort_unless(
            $organization !== null,
            403
        );

        $rateLimitKey =
            'support-request:user:'
            .$user->id;

        if (
            RateLimiter::tooManyAttempts(
                $rateLimitKey,
                5
            )
        ) {
            $minutes =
                max(
                    1,
                    (int) ceil(
                        RateLimiter::availableIn(
                            $rateLimitKey
                        ) / 60
                    )
                );

            return back()
                ->withErrors(
                    [
                        'support_message' =>
                            'You have sent several support requests. '
                            .'Please try again in about '
                            .$minutes
                            .' minute'
                            .($minutes === 1 ? '' : 's')
                            .'.',
                    ],
                    'supportRequest'
                )
                ->withInput();
        }

        $superAdmins =
            User::query()
                ->whereNull(
                    'organization_id'
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereNotNull(
                    'email_verified_at'
                )
                ->whereHas(
                    'platformRole',
                    function ($query): void {
                        $query->where(
                            'slug',
                            'super-admin'
                        );
                    }
                )
                ->get();

        if ($superAdmins->isEmpty()) {
            return back()
                ->withErrors(
                    [
                        'support' =>
                            'Support messaging is temporarily '
                            .'unavailable. Please try again later.',
                    ],
                    'supportRequest'
                )
                ->withInput();
        }

        $pageRoute =
            $this->contextValue(
                $validated[
                    'support_context_route'
                ] ?? null
            );

        $pagePath =
            $this->contextValue(
                $validated[
                    'support_context_path'
                ] ?? null
            );

        $helpContext =
            $this->contextValue(
                $validated[
                    'support_help_context'
                ] ?? null
            );

        $supportReference =
            $this->supportReference();

        /*
         * Deliberately audit metadata only.
         *
         * The user's support message is NOT written to the activity log.
         * Support messages may contain personal or otherwise sensitive
         * information and should remain confined to the support email.
         */
        $this->activityLogger->log(
            action:
                'support.requested',
            description:
                "Submitted support request "
                ."{$supportReference}.",
            subject:
                $user,
            organizationId:
                $organization->id,
            properties: [
                'reference' =>
                    $supportReference,

                'request_type' =>
                    $validated[
                        'support_type'
                    ],

                'requester_user_id' =>
                    (int) $user->id,

                'organization_id' =>
                    (int) $organization->id,

                'page_route' =>
                    $pageRoute,

                'page_path' =>
                    $pagePath,

                'help_context' =>
                    $helpContext,
            ]
        );

        Notification::send(
            $superAdmins,
            new SupportRequestSubmitted(
                supportReference:
                    $supportReference,

                requestType:
                    $validated[
                        'support_type'
                    ],

                requesterName:
                    $user->name,

                requesterEmail:
                    $user->email,

                organizationName:
                    $organization->name,

                messageBody:
                    trim(
                        $validated[
                            'support_message'
                        ]
                    ),

                pageRoute:
                    $pageRoute,

                pagePath:
                    $pagePath,

                helpContext:
                    $helpContext,

                submittedAt:
                    $this->submittedAt()
            )
        );

        RateLimiter::hit(
            $rateLimitKey,
            3600
        );

        return back()
            ->with(
                'support_request_status',
                'Your message was sent to eConsent Support. '
                .'Reference: '
                .$supportReference
                .'.'
            );
    }

    private function contextValue(
        mixed $value
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value =
            trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function supportReference(): string
    {
        $timezone =
            (string) config(
                'app.display_timezone',
                config(
                    'app.timezone',
                    'UTC'
                )
            );

        return 'SUP-'
            .now()
                ->timezone($timezone)
                ->format('Ymd')
            .'-'
            .Str::upper(
                Str::random(8)
            );
    }

    private function submittedAt(): string
    {
        $timezone =
            (string) config(
                'app.display_timezone',
                config(
                    'app.timezone',
                    'UTC'
                )
            );

        return now()
            ->timezone($timezone)
            ->format(
                'M d, Y H:i T'
            );
    }
}
