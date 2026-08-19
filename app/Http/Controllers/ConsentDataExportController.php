<?php

namespace App\Http\Controllers;

use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\SigningStation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
