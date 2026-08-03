<?php

namespace App\Http\Controllers;

use App\Models\ConsentSession;
use App\Services\ConsentAuditService;
use App\Services\ConsentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsentPdfController extends Controller
{
    /**
     * Download the stored consent PDF.
     */
    public function download(
        ConsentSession $consentSession,
        Request $request,
        ConsentPdfService $consentPdfService,
        ConsentAuditService $consentAuditService
    ): StreamedResponse {
        $this->ensureSessionBelongsToOrganization(
            $consentSession
        );

        $pdfDisk =
            $consentPdfService->diskName();

        abort_if(
            empty($consentSession->pdf_path)
            || empty($consentSession->pdf_generated_at),
            404,
            'The consent PDF has not been generated yet.'
        );

        abort_unless(
            Storage::disk($pdfDisk)->exists(
                $consentSession->pdf_path
            ),
            404,
            'The consent PDF could not be found.'
        );

        $downloadFilename =
            $consentPdfService->downloadFilename(
                $consentSession
            );

        /*
         * Every authorized download is a separate audit event.
         * We intentionally do not prevent duplicate events here.
         */
        $consentAuditService->record(
            consentSession: $consentSession,
            eventType: 'consent.pdf_downloaded',
            description:
                'An organization user initiated a download of the completed consent PDF.',
            metadata: [
                'storage_disk' =>
                    $pdfDisk,

                'pdf_path' =>
                    $consentSession->pdf_path,

                'download_filename' =>
                    $downloadFilename,
            ],
            request: $request
        );

        return Storage::disk($pdfDisk)->download(
            $consentSession->pdf_path,
            $downloadFilename,
            [
                'Content-Type' =>
                    'application/pdf',
            ]
        );
    }

    /**
     * Prevent users from downloading records
     * belonging to another organization.
     */
    private function ensureSessionBelongsToOrganization(
        ConsentSession $consentSession
    ): void {
        abort_unless(
            (int) $consentSession->organization_id ===
                (int) Auth::user()->organization_id,
            403
        );
    }
}
