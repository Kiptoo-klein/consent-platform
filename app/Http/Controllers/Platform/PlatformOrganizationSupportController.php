<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ConsentSession;
use App\Models\Organization;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ConsentAuditService;
use App\Services\ConsentPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformOrganizationSupportController extends Controller
{
    public function __construct(
        protected ActivityLogger $activityLogger
    ) {
    }

    public function index(
        Organization $organization
    ): View {
        $users = $organization
            ->users()
            ->with('roles')
            ->orderBy('name')
            ->get();

        $consentRecordCount = ConsentSession::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->count();

        $completedRecordCount = ConsentSession::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->where(
                'status',
                ConsentSession::STATUS_COMPLETED
            )
            ->count();

        return view(
            'platform.organizations.support.index',
            compact(
                'organization',
                'users',
                'consentRecordCount',
                'completedRecordCount'
            )
        );
    }

    public function consentRecords(
        Request $request,
        Organization $organization
    ): View {
        $search = trim(
            (string) $request->query(
                'q',
                ''
            )
        );

        $status = trim(
            (string) $request->query(
                'status',
                ''
            )
        );

        $allowedStatuses = [
            ConsentSession::STATUS_PENDING,
            ConsentSession::STATUS_IN_PROGRESS,
            ConsentSession::STATUS_COMPLETED,
            ConsentSession::STATUS_CANCELLED,
        ];

        if (! in_array(
            $status,
            $allowedStatuses,
            true
        )) {
            $status = '';
        }

        $consentSessions = ConsentSession::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->with([
                'consentTemplate',
                'creator',
                'signingStation',
            ])
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use (
                            $search
                        ): void {
                            $query
                                ->where(
                                    'signer_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'signer_email',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'signer_reference',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'consentTemplate',
                                    function ($query) use (
                                        $search
                                    ): void {
                                        $query->where(
                                            'title',
                                            'like',
                                            "%{$search}%"
                                        );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $status !== '',
                fn ($query) =>
                    $query->where(
                        'status',
                        $status
                    )
            )
            ->orderByDesc('completed_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view(
            'platform.organizations.support.consent-records',
            compact(
                'organization',
                'consentSessions',
                'search',
                'status',
                'allowedStatuses'
            )
        );
    }

    public function downloadConsentRecord(
        Request $request,
        Organization $organization,
        ConsentSession $consentSession,
        ConsentPdfService $consentPdfService,
        ConsentAuditService $consentAuditService
    ): StreamedResponse|RedirectResponse {
        $this->ensureConsentBelongsToOrganization(
            $organization,
            $consentSession
        );

        $validated = $request->validate([
            'support_reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ], [
            'support_reason.required' =>
                'Enter the organization support reason.',
            'support_reason.min' =>
                'The support reason must contain at least 10 characters.',
        ]);

        if (
            ! $consentSession->isCompleted()
            || blank($consentSession->pdf_path)
            || blank($consentSession->pdf_generated_at)
        ) {
            return back()->withErrors([
                'support_download' =>
                    'Only completed records with a generated PDF '
                    .'can be recovered.',
            ]);
        }

        $diskName =
            $consentPdfService->diskName();

        try {
            $pdfStream =
                Storage::disk(
                    $diskName
                )->readStream(
                    $consentSession->pdf_path
                );
        } catch (\Throwable) {
            $pdfStream = false;
        }

        if (! is_resource($pdfStream)) {
            return back()->withErrors([
                'support_download' =>
                    'The PDF record could not be found in storage.',
            ]);
        }

        $filename =
            $consentPdfService->downloadFilename(
                $consentSession
            );

        $consentAuditService->record(
            consentSession:
                $consentSession,
            eventType:
                'consent.platform_support_pdf_downloaded',
            description:
                'A platform administrator downloaded the '
                .'completed consent PDF for organization support.',
            metadata: [
                'support_reason' =>
                    $validated['support_reason'],
                'support_source' =>
                    'platform_organization_support',
                'storage_disk' =>
                    $diskName,
                'download_filename' =>
                    $filename,
            ],
            request: $request
        );

        $this->activityLogger->log(
            action:
                'support.consent_pdf_downloaded',
            description:
                "Recovered consent PDF {$consentSession->id} "
                ."for {$organization->name}.",
            subject:
                $consentSession,
            organizationId:
                $organization->id,
            properties: [
                'consent_session_id' =>
                    $consentSession->id,
                'support_reason' =>
                    $validated['support_reason'],
                'download_filename' =>
                    $filename,
            ],
        );

        return response()->streamDownload(
            static function () use ($pdfStream): void {
                try {
                    fpassthru($pdfStream);
                } finally {
                    fclose($pdfStream);
                }
            },
            $filename,
            [
                'Content-Type' =>
                    'application/pdf',
            ]
        );
    }

    public function sendPasswordResetLink(
        Request $request,
        Organization $organization,
        User $user
    ): RedirectResponse {
        $this->ensureUserBelongsToOrganization(
            $organization,
            $user
        );

        $validated = $request->validate([
            'support_reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ], [
            'support_reason.required' =>
                'Enter the account-recovery reason.',
            'support_reason.min' =>
                'The recovery reason must contain at least 10 characters.',
        ]);

        if (! $user->is_active) {
            return back()->withErrors([
                'password_reset' =>
                    'Enable this account before sending a '
                    .'password-reset link.',
            ]);
        }

        $status = Password::sendResetLink([
            'email' =>
                $user->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors([
                'password_reset' =>
                    __($status),
            ]);
        }

        $this->activityLogger->log(
            action:
                'support.password_reset_link_sent',
            description:
                "Sent a password-reset link to {$user->name}.",
            subject:
                $user,
            organizationId:
                $organization->id,
            properties: [
                'user_id' =>
                    $user->id,
                'email' =>
                    $user->email,
                'support_reason' =>
                    $validated['support_reason'],
            ],
        );

        return back()->with(
            'success',
            "A secure password-reset link was sent to {$user->email}."
        );
    }

    private function ensureConsentBelongsToOrganization(
        Organization $organization,
        ConsentSession $consentSession
    ): void {
        abort_unless(
            (int) $consentSession->organization_id
                === (int) $organization->id,
            404
        );
    }

    private function ensureUserBelongsToOrganization(
        Organization $organization,
        User $user
    ): void {
        abort_unless(
            (int) $user->organization_id
                === (int) $organization->id,
            404
        );
    }
}
