<?php

namespace App\Http\Controllers;

use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\SigningStation;
use App\Services\ConsentAuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class ConsentBulkDownloadController extends Controller
{
    /**
     * Download matching completed consent PDFs as one ZIP archive.
     */
    public function download(
        Request $request,
        ConsentAuditService $consentAuditService
    ): BinaryFileResponse|RedirectResponse {
        $organizationId =
            (int) $request->user()->organization_id;

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'template_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'consent_templates',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'organization_id',
                            $organizationId
                        )
                ),
            ],

            'category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'string',
                Rule::in([
                    ConsentSession::STATUS_PENDING,
                    ConsentSession::STATUS_IN_PROGRESS,
                    ConsentSession::STATUS_COMPLETED,
                    ConsentSession::STATUS_CANCELLED,
                    ConsentSession::STATUS_EXPIRED,
                ]),
            ],

            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'signing_station_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'signing_stations',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'organization_id',
                            $organizationId
                        )
                ),
            ],

            'sort' => [
                'nullable',
                'string',
                Rule::in([
                    'newest',
                    'oldest',
                ]),
            ],
        ]);

        $search = isset($validated['search'])
            ? trim($validated['search'])
            : null;

        if ($search === '') {
            $search = null;
        }

        $templateId = isset($validated['template_id'])
            ? (int) $validated['template_id']
            : null;

        $category = isset($validated['category'])
            ? trim($validated['category'])
            : null;

        if ($category === '') {
            $category = null;
        }

        $status = isset($validated['status'])
            ? trim($validated['status'])
            : null;

        if ($status === '') {
            $status = null;
        }

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        if (
            $dateFrom !== null
            && $dateTo !== null
            && $dateTo < $dateFrom
        ) {
            throw ValidationException::withMessages([
                'date_to' =>
                    'The end date must be on or after the start date.',
            ]);
        }

        $signingStationId =
            isset($validated['signing_station_id'])
                ? (int) $validated['signing_station_id']
                : null;

        $sort = $validated['sort'] ?? 'newest';

        $redirectParameters = array_filter(
            [
                'search' => $search,
                'template_id' => $templateId,
                'category' => $category,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'signing_station_id' =>
                    $signingStationId,
                'sort' => $sort,
            ],
            fn ($value, string $key): bool =>
                $value !== null
                && $value !== ''
                && ! (
                    $key === 'sort'
                    && $value === 'newest'
                ),
            ARRAY_FILTER_USE_BOTH
        );

        if (! class_exists(ZipArchive::class)) {
            return redirect()
                ->route(
                    'consent-sessions.index',
                    $redirectParameters
                )
                ->withErrors([
                    'download' =>
                        'ZIP support is not installed on the server. Install the PHP ZIP extension and try again.',
                ]);
        }

        $template = $templateId === null
            ? null
            : ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->findOrFail($templateId);

        $station = $signingStationId === null
            ? null
            : SigningStation::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->findOrFail($signingStationId);

        $matchingQuery =
            $this->filteredConsentSessionsQuery(
                organizationId:
                    $organizationId,
                templateId:
                    $templateId,
                category:
                    $category,
                search:
                    $search,
                status:
                    $status,
                dateFrom:
                    $dateFrom,
                dateTo:
                    $dateTo,
                signingStationId:
                    $signingStationId
            )
                ->where(
                    'status',
                    ConsentSession::STATUS_COMPLETED
                )
                ->whereNotNull('pdf_path')
                ->whereNotNull('pdf_generated_at')
                ->with([
                    'consentTemplate:id,title,category',
                ]);

        if (! (clone $matchingQuery)->exists()) {
            return redirect()
                ->route(
                    'consent-sessions.index',
                    $redirectParameters
                )
                ->withErrors([
                    'download' =>
                        'No completed consent records with generated PDFs match the active filters.',
                ]);
        }

        $archiveName = $this->archiveName(
            template: $template,
            category: $category
        );

        $temporaryDirectory =
            'consent-exports/tmp';

        $temporaryRelativePath =
            $temporaryDirectory
            .'/'.Str::uuid().'.zip';

        Storage::disk('local')->makeDirectory(
            $temporaryDirectory
        );

        $temporaryAbsolutePath =
            Storage::disk('local')->path(
                $temporaryRelativePath
            );

        $zip = new ZipArchive();

        $openResult = $zip->open(
            $temporaryAbsolutePath,
            ZipArchive::CREATE
                | ZipArchive::OVERWRITE
        );

        if ($openResult !== true) {
            return redirect()
                ->route(
                    'consent-sessions.index',
                    $redirectParameters
                )
                ->withErrors([
                    'download' =>
                        'The ZIP archive could not be created. Please try again.',
                ]);
        }

        $includedSessionIds = [];
        $includedFileNames = [];
        $archiveClosed = false;

        try {
            foreach (
                (clone $matchingQuery)
                    ->orderBy('id')
                    ->lazyById(100)
                as $consentSession
            ) {
                $absolutePdfPath =
                    $this->privatePdfPath(
                        (string) $consentSession->pdf_path
                    );

                if ($absolutePdfPath === null) {
                    continue;
                }

                $entryName =
                    $this->archiveEntryName(
                        $consentSession
                    );

                if (! $zip->addFile(
                    $absolutePdfPath,
                    $entryName
                )) {
                    continue;
                }

                $includedSessionIds[] =
                    (int) $consentSession->id;

                $includedFileNames[
                    (int) $consentSession->id
                ] = $entryName;
            }

            if (! $zip->close()) {
                throw new RuntimeException(
                    'The ZIP archive could not be finalized.'
                );
            }

            $archiveClosed = true;
        } catch (Throwable $exception) {
            if (! $archiveClosed) {
                $zip->close();
            }

            Storage::disk('local')->delete(
                $temporaryRelativePath
            );

            report($exception);

            return redirect()
                ->route(
                    'consent-sessions.index',
                    $redirectParameters
                )
                ->withErrors([
                    'download' =>
                        'The PDF archive could not be completed. Please try again.',
                ]);
        }

        if ($includedSessionIds === []) {
            Storage::disk('local')->delete(
                $temporaryRelativePath
            );

            return redirect()
                ->route(
                    'consent-sessions.index',
                    $redirectParameters
                )
                ->withErrors([
                    'download' =>
                        'Matching records were found, but their stored PDF files are unavailable.',
                ]);
        }

        try {
            DB::transaction(
                function () use (
                    $includedSessionIds,
                    $includedFileNames,
                    $organizationId,
                    $archiveName,
                    $template,
                    $category,
                    $search,
                    $status,
                    $dateFrom,
                    $dateTo,
                    $station,
                    $sort,
                    $request,
                    $consentAuditService
                ): void {
                    $recordCount = count(
                        $includedSessionIds
                    );

                    foreach (
                        array_chunk(
                            $includedSessionIds,
                            100
                        )
                        as $sessionIdChunk
                    ) {
                        $sessions =
                            ConsentSession::query()
                                ->where(
                                    'organization_id',
                                    $organizationId
                                )
                                ->whereIn(
                                    'id',
                                    $sessionIdChunk
                                )
                                ->orderBy('id')
                                ->get();

                        foreach ($sessions as $session) {
                            $consentAuditService->record(
                                consentSession: $session,
                                eventType:
                                    'consent.bulk_export_downloaded',
                                description:
                                    'The consent PDF was included in a bulk ZIP download.',
                                metadata: [
                                    'export_type' =>
                                        'bulk_pdf_zip',

                                    'archive_name' =>
                                        $archiveName,

                                    'record_count' =>
                                        $recordCount,

                                    'included_file_name' =>
                                        $includedFileNames[
                                            (int) $session->id
                                        ] ?? null,

                                    'filter_search' =>
                                        $search,

                                    'filter_template_id' =>
                                        $template?->id,

                                    'filter_template_title' =>
                                        $template?->title,

                                    'filter_category' =>
                                        $category,

                                    'filter_status' =>
                                        $status,

                                    'filter_date_from' =>
                                        $dateFrom,

                                    'filter_date_to' =>
                                        $dateTo,

                                    'filter_signing_station_id' =>
                                        $station?->id,

                                    'filter_signing_station_name' =>
                                        $station?->name,

                                    'sort_order' =>
                                        $sort,
                                ],
                                request: $request
                            );
                        }
                    }
                },
                3
            );
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(
                $temporaryRelativePath
            );

            throw $exception;
        }

        return response()
            ->download(
                $temporaryAbsolutePath,
                $archiveName,
                [
                    'Content-Type' =>
                        'application/zip',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    /**
     * Build a tenant-safe query for the active record filters.
     */
    private function filteredConsentSessionsQuery(
        int $organizationId,
        ?int $templateId,
        ?string $category,
        ?string $search,
        ?string $status,
        ?string $dateFrom,
        ?string $dateTo,
        ?int $signingStationId
    ): Builder {
        return ConsentSession::query()
            ->where(
                'organization_id',
                $organizationId
            )
            ->when(
                $search !== null,
                function (
                    Builder $query
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $searchQuery
                        ) use ($search): void {
                            if (ctype_digit($search)) {
                                $searchQuery->where(
                                    'id',
                                    (int) $search
                                );
                            } else {
                                $searchQuery->where(
                                    'signer_name',
                                    'like',
                                    '%'.$search.'%'
                                );
                            }

                            $searchQuery
                                ->orWhere(
                                    'signer_name',
                                    'like',
                                    '%'.$search.'%'
                                )
                                ->orWhere(
                                    'signer_email',
                                    'like',
                                    '%'.$search.'%'
                                )
                                ->orWhere(
                                    'signer_reference',
                                    'like',
                                    '%'.$search.'%'
                                );
                        }
                    );
                }
            )
            ->when(
                $templateId !== null,
                fn (Builder $query): Builder =>
                    $query->where(
                        'consent_template_id',
                        $templateId
                    )
            )
            ->when(
                $category !== null,
                fn (Builder $query): Builder =>
                    $query->whereHas(
                        'consentTemplate',
                        fn (
                            Builder $templateQuery
                        ): Builder =>
                            $templateQuery->where(
                                'category',
                                $category
                            )
                    )
            )
            ->when(
                $status !== null,
                fn (Builder $query): Builder =>
                    $query->where(
                        'status',
                        $status
                    )
            )
            ->when(
                $dateFrom !== null,
                fn (Builder $query): Builder =>
                    $query->whereDate(
                        'created_at',
                        '>=',
                        $dateFrom
                    )
            )
            ->when(
                $dateTo !== null,
                fn (Builder $query): Builder =>
                    $query->whereDate(
                        'created_at',
                        '<=',
                        $dateTo
                    )
            )
            ->when(
                $signingStationId !== null,
                fn (Builder $query): Builder =>
                    $query->where(
                        'signing_station_id',
                        $signingStationId
                    )
            );
    }

    /**
     * Resolve a stored private PDF path safely.
     */
    private function privatePdfPath(
        string $storedPath
    ): ?string {
        $normalizedPath = ltrim(
            str_replace('\\', '/', $storedPath),
            '/'
        );

        if (
            $normalizedPath === ''
            || str_contains($normalizedPath, '../')
        ) {
            return null;
        }

        $candidatePaths = [$normalizedPath];

        if (str_starts_with(
            $normalizedPath,
            'private/'
        )) {
            $candidatePaths[] = substr(
                $normalizedPath,
                strlen('private/')
            );
        }

        foreach (array_unique($candidatePaths) as $candidate) {
            if (! Storage::disk('local')->exists($candidate)) {
                continue;
            }

            $absolutePath = Storage::disk('local')->path(
                $candidate
            );

            if (
                is_file($absolutePath)
                && is_readable($absolutePath)
            ) {
                return $absolutePath;
            }
        }

        return null;
    }

    /**
     * Build a unique, readable filename inside the ZIP archive.
     */
    private function archiveEntryName(
        ConsentSession $consentSession
    ): string {
        $templateSlug = Str::slug(
            $consentSession->consentTemplate?->title
                ?? 'consent'
        );

        $signerSlug = Str::slug(
            $consentSession->signer_name
        );

        return sprintf(
            '%06d_%s_%s.pdf',
            $consentSession->id,
            $templateSlug !== ''
                ? $templateSlug
                : 'consent',
            $signerSlug !== ''
                ? $signerSlug
                : 'signer'
        );
    }

    /**
     * Build the downloaded ZIP filename from the active filters.
     */
    private function archiveName(
        ?ConsentTemplate $template,
        ?string $category
    ): string {
        $label = match (true) {
            $template !== null =>
                'template-'.Str::slug($template->title),

            $category !== null =>
                'category-'.Str::slug($category),

            default =>
                'all-templates',
        };

        $label = trim($label, '-');

        if ($label === '') {
            $label = 'selected-records';
        }

        return 'consent-records-'
            .$label
            .'-'.now()->format('Y-m-d-His')
            .'.zip';
    }
}

