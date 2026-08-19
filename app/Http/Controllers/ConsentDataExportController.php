<?php

namespace App\Http\Controllers;

use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\SigningStation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Writer\XLSX\Options as XlsxOptions;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ConsentDataExportController extends Controller
{
    /**
     * Configure a structured data export for the current record scope.
     */
    public function index(
        Request $request
    ): View {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'template_id' => [
                'nullable',
                'integer',
            ],

            'category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'string',
                'in:pending,in_progress,completed,cancelled,expired',
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
            ],

            'sort' => [
                'nullable',
                'string',
                'in:newest,oldest',
            ],
        ]);

        $organizationId =
            (int) $request->user()->organization_id;

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

        $dateFrom =
            $validated['date_from']
            ?? null;

        $dateTo =
            $validated['date_to']
            ?? null;

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

        $sort =
            $validated['sort']
            ?? 'newest';

        $templates =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->orderBy('title')
                ->get([
                    'id',
                    'title',
                    'category',
                ]);

        if (
            $templateId !== null
            && ! $templates->contains(
                fn (ConsentTemplate $template): bool =>
                    (int) $template->id ===
                    $templateId
            )
        ) {
            abort(403);
        }

        $signingStations =
            SigningStation::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]);

        if (
            $signingStationId !== null
            && ! $signingStations->contains(
                fn (SigningStation $station): bool =>
                    (int) $station->id ===
                    $signingStationId
            )
        ) {
            abort(403);
        }

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
            );

        $matchingCount =
            (clone $matchingQuery)->count();

        [
            $signerColumns,
            $questionColumns,
        ] = $this->discoverAvailableColumns(
            clone $matchingQuery
        );

        $scopeParameters =
            array_filter(
                [
                    'search' =>
                        $search,

                    'template_id' =>
                        $templateId,

                    'category' =>
                        $category,

                    'status' =>
                        $status,

                    'date_from' =>
                        $dateFrom,

                    'date_to' =>
                        $dateTo,

                    'signing_station_id' =>
                        $signingStationId,

                    'sort' =>
                        $sort,
                ],
                fn (
                    $value,
                    string $key
                ): bool =>
                    $value !== null
                    && $value !== ''
                    && ! (
                        $key === 'sort'
                        && $value === 'newest'
                    ),
                ARRAY_FILTER_USE_BOTH
            );

        return view(
            'consent-sessions.export-data',
            [
                'matchingCount' =>
                    $matchingCount,

                'signerColumns' =>
                    $signerColumns,

                'questionColumns' =>
                    $questionColumns,

                'scopeParameters' =>
                    $scopeParameters,

                'selectedTemplate' =>
                    $templateId === null
                        ? null
                        : $templates->firstWhere(
                            'id',
                            $templateId
                        ),
            ]
        );
    }

    /**
     * Generate the selected consent record data export.
     */
    public function download(
        Request $request
    ): BinaryFileResponse|StreamedResponse {
        $validated = $request->validate([
            'columns' => [
                'required',
                'array',
                'min:1',
                'max:250',
            ],

            'columns.*' => [
                'required',
                'string',
                'max:255',
            ],

            'format' => [
                'required',
                'string',
                'in:xlsx,csv',
            ],

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'template_id' => [
                'nullable',
                'integer',
            ],

            'category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'string',
                'in:pending,in_progress,completed,cancelled,expired',
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
            ],

            'sort' => [
                'nullable',
                'string',
                'in:newest,oldest',
            ],
        ]);

        $organizationId =
            (int) $request->user()->organization_id;

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

        $dateFrom =
            $validated['date_from']
            ?? null;

        $dateTo =
            $validated['date_to']
            ?? null;

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

        $sort =
            $validated['sort']
            ?? 'newest';

        $template = null;

        if ($templateId !== null) {
            $template =
                ConsentTemplate::query()
                    ->where(
                        'organization_id',
                        $organizationId
                    )
                    ->find($templateId);

            abort_if(
                $template === null,
                403
            );
        }

        if ($signingStationId !== null) {
            $stationExists =
                SigningStation::query()
                    ->where(
                        'organization_id',
                        $organizationId
                    )
                    ->whereKey(
                        $signingStationId
                    )
                    ->exists();

            abort_unless(
                $stationExists,
                403
            );
        }

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
            );

        [
            $signerColumns,
            $questionColumns,
        ] = $this->discoverAvailableColumns(
            clone $matchingQuery
        );

        $availableColumns =
            $signerColumns
                ->concat(
                    $questionColumns
                )
                ->keyBy('key');

        $requestedColumnKeys =
            collect(
                $validated['columns']
            )
                ->map(
                    fn ($key): string =>
                        (string) $key
                )
                ->unique()
                ->values();

        $invalidColumnKeys =
            $requestedColumnKeys
                ->reject(
                    fn (string $key): bool =>
                        $availableColumns->has(
                            $key
                        )
                )
                ->values();

        if ($invalidColumnKeys->isNotEmpty()) {
            throw ValidationException::withMessages([
                'columns' =>
                    'One or more selected columns are no longer available. Refresh the export page and try again.',
            ]);
        }

        $selectedColumns =
            $requestedColumnKeys
                ->map(
                    fn (string $key): array =>
                        $availableColumns->get(
                            $key
                        )
                )
                ->values();

        if ($selectedColumns->isEmpty()) {
            throw ValidationException::withMessages([
                'columns' =>
                    'Choose at least one available column to export.',
            ]);
        }

        $exportQuery =
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
                ->with([
                    'consentTemplateVersion:id,consent_template_id,template_schema',
                ]);

        $format =
            (string) $validated['format'];

        $filename =
            $this->exportFilename(
                template: $template,
                format: $format
            );

        $singleTemplate =
            $templateId !== null;

        if ($format === 'csv') {
            return $this->csvDownload(
                query: $exportQuery,
                columns: $selectedColumns,
                filename: $filename,
                sort: $sort,
                singleTemplate:
                    $singleTemplate
            );
        }

        return $this->xlsxDownload(
            query: $exportQuery,
            columns: $selectedColumns,
            filename: $filename,
            sort: $sort,
            singleTemplate:
                $singleTemplate
        );
    }

    /**
     * Discover only columns that contain data in the current record scope.
     */
    private function discoverAvailableColumns(
        Builder $query
    ): array {
        $hasSignerName = false;
        $hasSignerEmail = false;

        $questionColumns = [];

        foreach (
            $query
                ->with([
                    'consentTemplate:id,title',
                    'consentTemplateVersion:id,consent_template_id,template_schema',
                ])
                ->lazyById(100)
            as $consentSession
        ) {
            if (
                $this->hasValue(
                    $consentSession->signer_name
                )
            ) {
                $hasSignerName = true;
            }

            if (
                $this->hasValue(
                    $consentSession->signer_email
                )
            ) {
                $hasSignerEmail = true;
            }

            $responses =
                $consentSession->responses
                ?? [];

            if (! is_array($responses)) {
                continue;
            }

            $fields = data_get(
                $consentSession
                    ->consentTemplateVersion,
                'template_schema.additional_fields',
                []
            );

            if (! is_array($fields)) {
                continue;
            }

            foreach (
                array_values($fields)
                as $index => $field
            ) {
                if (! is_array($field)) {
                    continue;
                }

                $fieldKey =
                    $this->fieldKey(
                        $field,
                        $index
                    );

                if (
                    ! array_key_exists(
                        $fieldKey,
                        $responses
                    )
                    || ! $this->hasValue(
                        $responses[$fieldKey]
                    )
                ) {
                    continue;
                }

                $templateId =
                    (int) $consentSession
                        ->consent_template_id;

                $columnKey =
                    'question:'
                    .$templateId
                    .':'
                    .$fieldKey;

                if (
                    isset(
                        $questionColumns[
                            $columnKey
                        ]
                    )
                ) {
                    continue;
                }

                $fieldType =
                    isset($field['type'])
                    && is_string(
                        $field['type']
                    )
                        ? $field['type']
                        : 'text';

                $label =
                    isset($field['label'])
                    && is_string(
                        $field['label']
                    )
                    && trim(
                        $field['label']
                    ) !== ''
                        ? trim(
                            $field['label']
                        )
                        : $this->humanizeKey(
                            $fieldKey
                        );

                $questionColumns[
                    $columnKey
                ] = [
                    'key' =>
                        $columnKey,

                    'label' =>
                        $label,

                    'type' =>
                        $fieldType,

                    'template_title' =>
                        $consentSession
                            ->consentTemplate
                            ?->title
                        ?? 'Consent',

                    'selected_by_default' =>
                        $fieldType === 'phone',
                ];
            }
        }

        $signerColumns = [];

        if ($hasSignerName) {
            $signerColumns[] = [
                'key' =>
                    'signer_name',

                'label' =>
                    'Name',

                'selected_by_default' =>
                    true,
            ];
        }

        if ($hasSignerEmail) {
            $signerColumns[] = [
                'key' =>
                    'signer_email',

                'label' =>
                    'Email',

                'selected_by_default' =>
                    true,
            ];
        }

        $questionColumns =
            collect(
                array_values(
                    $questionColumns
                )
            )
                ->sortBy([
                    [
                        'template_title',
                        'asc',
                    ],
                    [
                        'label',
                        'asc',
                    ],
                ])
                ->values();

        $phoneColumns =
            $questionColumns
                ->filter(
                    fn (array $column): bool =>
                        $column['type'] === 'phone'
                )
                ->values();

        $questionColumns =
            $questionColumns
                ->reject(
                    fn (array $column): bool =>
                        $column['type'] === 'phone'
                )
                ->values();

        return [
            collect($signerColumns)
                ->concat($phoneColumns)
                ->values(),

            $questionColumns,
        ];
    }

    /**
     * Build a tenant-safe record query using the current filters.
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
                            if (
                                ctype_digit(
                                    $search
                                )
                            ) {
                                $searchQuery
                                    ->where(
                                        'id',
                                        (int) $search
                                    );
                            } else {
                                $searchQuery
                                    ->where(
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
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'consent_template_id',
                        $templateId
                    )
            )
            ->when(
                $category !== null,
                fn (
                    Builder $query
                ): Builder =>
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
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'status',
                        $status
                    )
            )
            ->when(
                $dateFrom !== null,
                fn (
                    Builder $query
                ): Builder =>
                    $query->whereDate(
                        'created_at',
                        '>=',
                        $dateFrom
                    )
            )
            ->when(
                $dateTo !== null,
                fn (
                    Builder $query
                ): Builder =>
                    $query->whereDate(
                        'created_at',
                        '<=',
                        $dateTo
                    )
            )
            ->when(
                $signingStationId !== null,
                fn (
                    Builder $query
                ): Builder =>
                    $query->where(
                        'signing_station_id',
                        $signingStationId
                    )
            );
    }

    /**
     * Stream the selected records as CSV.
     */
    private function csvDownload(
        Builder $query,
        Collection $columns,
        string $filename,
        string $sort,
        bool $singleTemplate
    ): StreamedResponse {
        return response()->streamDownload(
            function () use (
                $query,
                $columns,
                $sort,
                $singleTemplate
            ): void {
                $handle = fopen(
                    'php://output',
                    'wb'
                );

                if ($handle === false) {
                    throw new RuntimeException(
                        'The CSV export stream could not be opened.'
                    );
                }

                try {
                    fwrite(
                        $handle,
                        "\xEF\xBB\xBF"
                    );

                    fputcsv(
                        $handle,
                        $columns
                            ->map(
                                fn (
                                    array $column
                                ): string =>
                                    $this->columnHeading(
                                        column:
                                            $column,

                                        singleTemplate:
                                            $singleTemplate
                                    )
                            )
                            ->all()
                    );

                    $this->forEachExportRecord(
                        query:
                            $query,

                        sort:
                            $sort,

                        callback:
                            function (
                                ConsentSession $session
                            ) use (
                                $handle,
                                $columns
                            ): void {
                                $row =
                                    $columns
                                        ->map(
                                            fn (
                                                array $column
                                            ): string =>
                                                $this->csvSafeValue(
                                                    $this->columnValue(
                                                        consentSession:
                                                            $session,

                                                        column:
                                                            $column
                                                    )
                                                )
                                        )
                                        ->all();

                                fputcsv(
                                    $handle,
                                    $row
                                );
                            }
                    );
                } finally {
                    fclose($handle);
                }
            },
            $filename,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

    /**
     * Generate the selected records as an XLSX workbook.
     */
    private function xlsxDownload(
        Builder $query,
        Collection $columns,
        string $filename,
        string $sort,
        bool $singleTemplate
    ): BinaryFileResponse {
        $temporaryDirectory =
            'consent-exports/tmp';

        $temporaryRelativePath =
            $temporaryDirectory
            .'/'
            .Str::uuid()
            .'.xlsx';

        Storage::disk('local')
            ->makeDirectory(
                $temporaryDirectory
            );

        $temporaryAbsolutePath =
            Storage::disk('local')
                ->path(
                    $temporaryRelativePath
                );

        $writer =
            new XlsxWriter(
                new XlsxOptions(
                    SHOULD_USE_INLINE_STRINGS:
                        false
                )
            );

        $writerOpen = false;

        try {
            $writer->openToFile(
                $temporaryAbsolutePath
            );

            $writerOpen = true;

            $writer->addRow(
                $this->xlsxStringRow(
                    $columns
                        ->map(
                            fn (
                                array $column
                            ): string =>
                                $this->columnHeading(
                                    column:
                                        $column,

                                    singleTemplate:
                                        $singleTemplate
                                )
                        )
                        ->all()
                )
            );

            $this->forEachExportRecord(
                query:
                    $query,

                sort:
                    $sort,

                callback:
                    function (
                        ConsentSession $session
                    ) use (
                        $writer,
                        $columns
                    ): void {
                        $writer->addRow(
                            $this->xlsxStringRow(
                                $columns
                                    ->map(
                                        fn (
                                            array $column
                                        ): string =>
                                            $this->columnValue(
                                                consentSession:
                                                    $session,

                                                column:
                                                    $column
                                            )
                                    )
                                    ->all()
                            )
                        );
                    }
            );

            $writer->close();

            $writerOpen = false;
        } catch (Throwable $exception) {
            if ($writerOpen) {
                try {
                    $writer->close();
                } catch (Throwable) {
                    // Preserve the original export exception.
                }
            }

            Storage::disk('local')
                ->delete(
                    $temporaryRelativePath
                );

            throw $exception;
        }

        return response()
            ->download(
                $temporaryAbsolutePath,
                $filename,
                [
                    'Content-Type' =>
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )
            ->deleteFileAfterSend(
                true
            );
    }

    /**
     * Create an XLSX row containing only literal string cells.
     */
    private function xlsxStringRow(
        array $values
    ): Row {
        return new Row(
            array_values(
                array_map(
                    fn ($value): StringCell =>
                        new StringCell(
                            (string) $value
                        ),
                    $values
                )
            )
        );
    }

    /**
     * Process matching records in bounded batches.
     */
    private function forEachExportRecord(
        Builder $query,
        string $sort,
        callable $callback
    ): void {
        if ($sort === 'oldest') {
            $query
                ->orderBy('created_at')
                ->orderBy('id');
        } else {
            $query
                ->orderByDesc('created_at')
                ->orderByDesc('id');
        }

        $query->chunk(
            200,
            function (
                $consentSessions
            ) use (
                $callback
            ): void {
                foreach (
                    $consentSessions
                    as $consentSession
                ) {
                    $callback(
                        $consentSession
                    );
                }
            }
        );
    }

    /**
     * Resolve one selected column for one consent record.
     */
    private function columnValue(
        ConsentSession $consentSession,
        array $column
    ): string {
        $columnKey =
            (string) (
                $column['key']
                ?? ''
            );

        if ($columnKey === 'signer_name') {
            return $this->formatExportValue(
                $consentSession->signer_name
            );
        }

        if ($columnKey === 'signer_email') {
            return $this->formatExportValue(
                $consentSession->signer_email
            );
        }

        if (
            ! str_starts_with(
                $columnKey,
                'question:'
            )
        ) {
            return '';
        }

        $parts = explode(
            ':',
            $columnKey,
            3
        );

        if (count($parts) !== 3) {
            return '';
        }

        $templateId =
            (int) $parts[1];

        $fieldKey =
            $parts[2];

        if (
            (int) $consentSession
                ->consent_template_id
            !== $templateId
        ) {
            return '';
        }

        $responses =
            $consentSession->responses
            ?? [];

        if (
            ! is_array($responses)
            || ! array_key_exists(
                $fieldKey,
                $responses
            )
        ) {
            return '';
        }

        $field =
            $this->fieldDefinitionForSession(
                consentSession:
                    $consentSession,

                fieldKey:
                    $fieldKey
            );

        if ($field === null) {
            return '';
        }

        $type =
            isset($field['type'])
            && is_string(
                $field['type']
            )
                ? $field['type']
                : 'text';

        return $this->formatExportValue(
            value:
                $responses[$fieldKey],

            type:
                $type
        );
    }

    /**
     * Find a field only in the exact published version used by the record.
     */
    private function fieldDefinitionForSession(
        ConsentSession $consentSession,
        string $fieldKey
    ): ?array {
        $fields = data_get(
            $consentSession
                ->consentTemplateVersion,
            'template_schema.additional_fields',
            []
        );

        if (! is_array($fields)) {
            return null;
        }

        foreach (
            array_values($fields)
            as $index => $field
        ) {
            if (! is_array($field)) {
                continue;
            }

            if (
                $this->fieldKey(
                    $field,
                    $index
                ) === $fieldKey
            ) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Format stored answers for spreadsheet cells.
     */
    private function formatExportValue(
        mixed $value,
        ?string $type = null
    ): string {
        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            return implode(
                ', ',
                array_map(
                    fn ($item): string =>
                        $this->formatExportValue(
                            $item
                        ),
                    $value
                )
            );
        }

        if (is_bool($value)) {
            return $value
                ? 'Yes'
                : 'No';
        }

        if (
            in_array(
                $type,
                [
                    'checkbox',
                    'yes_no',
                ],
                true
            )
        ) {
            return in_array(
                $value,
                [
                    1,
                    '1',
                    'true',
                    'yes',
                    'on',
                ],
                true
            )
                ? 'Yes'
                : 'No';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded =
            json_encode(
                $value,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            );

        return is_string($encoded)
            ? $encoded
            : '';
    }

    /**
     * Prevent spreadsheet software from interpreting CSV data as formulas.
     */
    private function csvSafeValue(
        string $value
    ): string {
        if (
            $value !== ''
            && preg_match(
                '/^[\x00-\x20]*[=+\-@]/u',
                $value
            ) === 1
        ) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Build a clear human-facing spreadsheet heading.
     */
    private function columnHeading(
        array $column,
        bool $singleTemplate
    ): string {
        $label =
            (string) (
                $column['label']
                ?? 'Column'
            );

        if (
            $singleTemplate
            || ! str_starts_with(
                (string) (
                    $column['key']
                    ?? ''
                ),
                'question:'
            )
        ) {
            return $label;
        }

        $templateTitle =
            trim(
                (string) (
                    $column['template_title']
                    ?? ''
                )
            );

        if ($templateTitle === '') {
            return $label;
        }

        return $label
            .' ('
            .$templateTitle
            .')';
    }

    /**
     * Build a simple download filename.
     */
    private function exportFilename(
        ?ConsentTemplate $template,
        string $format
    ): string {
        $prefix =
            $template === null
                ? 'consent-records'
                : (
                    Str::slug(
                        $template->title
                    )
                    .'-records'
                );

        return $prefix
            .'-'
            .now()->format('Y-m-d')
            .'.'
            .$format;
    }

    /**
     * Determine whether a value should create an export column.
     */
    private function hasValue(
        mixed $value
    ): bool {
        if (is_array($value)) {
            return $value !== [];
        }

        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }

    /**
     * Resolve the stable response key used by a structured field.
     */
    private function fieldKey(
        array $field,
        int $index
    ): string {
        return (string) (
            $field['name']
            ?? $field['key']
            ?? $field['id']
            ?? 'field_'.$index
        );
    }

    /**
     * Turn a technical fallback key into a readable label.
     */
    private function humanizeKey(
        string $key
    ): string {
        return Str::of($key)
            ->replace(
                [
                    '_',
                    '-',
                ],
                ' '
            )
            ->squish()
            ->title()
            ->toString();
    }
}
