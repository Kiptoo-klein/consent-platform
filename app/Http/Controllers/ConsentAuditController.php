<?php

namespace App\Http\Controllers;

use App\Models\ConsentSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConsentAuditController extends Controller
{
    /**
     * Display the audit trail for a consent record.
     */
    public function show(
        ConsentSession $consentSession
    ): View {
        $this->ensureSessionBelongsToOrganization(
            $consentSession
        );

        $consentSession->load([
            'organization',
            'consentTemplate',
            'consentTemplateVersion',
            'signature',

            'auditEvents' => function ($query): void {
                $query
                    ->with('user')
                    ->orderBy('created_at');
            },
        ]);

        return view('consent-audit.show', [
            'consentSession' => $consentSession,
            'auditEvents' => $consentSession->auditEvents,
        ]);
    }

    /**
     * Prevent users from viewing audit records
     * belonging to another organization.
     */
    private function ensureSessionBelongsToOrganization(
        ConsentSession $consentSession
    ): void {
        abort_unless(
            Auth::check()
            && (int) $consentSession->organization_id ===
                (int) Auth::user()->organization_id,
            403
        );
    }
}
