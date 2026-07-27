<?php

namespace App\Services;

use App\Mail\SignedConsentCopyMail;
use App\Models\ConsentPdfDelivery;
use App\Models\ConsentSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SignedConsentPdfDeliveryService
{
    public function deliver(int|ConsentSession $session, bool $force = false): bool
    {
        if (! config('signed-consent-delivery.enabled', true)) {
            return false;
        }

        $consentSession = $session instanceof ConsentSession
            ? $session->fresh()
            : ConsentSession::query()->findOrFail($session);

        $consentSession->loadMissing([
            'organization',
            'consentTemplate',
            'consentTemplateVersion',
            'signature',
            'signingStation',
        ]);

        if (
            config('signed-consent-delivery.public_signing_stations_only', true)
            && blank($consentSession->signing_station_id)
        ) {
            return false;
        }

        if (! $this->isCompleted($consentSession)) {
            return false;
        }

        $recipient = trim((string) $consentSession->signer_email);

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $delivery = ConsentPdfDelivery::query()->firstOrCreate(
            [
                'consent_session_id' => $consentSession->getKey(),
            ],
            [
                'recipient' => $recipient,
                'status' => ConsentPdfDelivery::STATUS_PENDING,
                'attempts' => 0,
            ]
        );

        $claimed = DB::transaction(function () use (
            $delivery,
            $recipient,
            $force
        ): bool {
            $locked = ConsentPdfDelivery::query()
                ->whereKey($delivery->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->sent_at && ! $force) {
                return false;
            }

            $staleAfterMinutes = max(
                1,
                (int) config(
                    'signed-consent-delivery.processing_stale_minutes',
                    10
                )
            );

            if (
                ! $force
                && $locked->status === ConsentPdfDelivery::STATUS_PROCESSING
                && $locked->processing_at
                && $locked->processing_at->gt(
                    now()->subMinutes($staleAfterMinutes)
                )
            ) {
                return false;
            }

            $locked->update([
                'recipient' => $recipient,
                'status' => ConsentPdfDelivery::STATUS_PROCESSING,
                'attempts' => $locked->attempts + 1,
                'processing_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ]);

            return true;
        });

        if (! $claimed) {
            return false;
        }

        try {
            $attachment = $this->resolveAttachment($consentSession);

            Mail::to($recipient)->send(
                new SignedConsentCopyMail(
                    $consentSession,
                    $attachment['data'],
                    $attachment['name']
                )
            );

            $delivery->refresh()->update([
                'status' => ConsentPdfDelivery::STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
                'last_error' => null,
                'metadata' => [
                    'attachment_name' => $attachment['name'],
                    'attachment_source' => $attachment['source'],
                    'mail_mailer' => config('mail.default'),
                ],
            ]);

            return true;
        } catch (Throwable $exception) {
            $delivery->refresh()->update([
                'status' => ConsentPdfDelivery::STATUS_FAILED,
                'failed_at' => now(),
                'last_error' => Str::limit($exception->getMessage(), 4000),
            ]);

            report($exception);

            throw $exception;
        }
    }

    private function isCompleted(ConsentSession $session): bool
    {
        if (method_exists($session, 'isCompleted')) {
            return (bool) $session->isCompleted();
        }

        return filled($session->completed_at)
            || strtolower((string) $session->status) === 'completed';
    }

    /**
     * @return array{data:string,name:string,source:string}
     */
    private function resolveAttachment(ConsentSession $session): array
    {
        foreach ($this->candidateValues($session) as $candidate) {
            $resolved = $this->resolveCandidate($candidate);

            if ($resolved) {
                return [
                    'data' => $resolved['data'],
                    'name' => $this->attachmentName($session),
                    'source' => $resolved['source'],
                ];
            }
        }

        $filesystemMatch = $this->findRecentStoredPdf($session);

        if ($filesystemMatch) {
            return [
                'data' => file_get_contents($filesystemMatch),
                'name' => $this->attachmentName($session),
                'source' => $filesystemMatch,
            ];
        }

        throw new RuntimeException(
            'The signed PDF was generated, but its stored file could not be located for email delivery.'
        );
    }

    /**
     * @return iterable<mixed>
     */
    private function candidateValues(ConsentSession $session): iterable
    {
        $keys = [
            'pdf_path',
            'pdf_file_path',
            'pdf_storage_path',
            'signed_pdf_path',
            'stored_pdf_path',
            'document_path',
            'file_path',
            'path',
        ];

        foreach ($keys as $key) {
            $value = data_get($session, $key);

            if (filled($value)) {
                yield $value;
            }
        }

        foreach ([
            'pdf',
            'consentPdf',
            'pdfDocument',
            'generatedPdf',
            'latestPdf',
            'document',
        ] as $relation) {
            if (! method_exists($session, $relation)) {
                continue;
            }

            try {
                $related = $session->{$relation}()->latest()->first();

                if ($related) {
                    yield $related;
                }
            } catch (Throwable) {
                // A relationship may not support latest(). Continue safely.
            }
        }

        yield from $this->databasePdfRows($session);
    }

    /**
     * @return iterable<mixed>
     */
    private function databasePdfRows(ConsentSession $session): iterable
    {
        if (! method_exists(Schema::getFacadeRoot(), 'getTables')) {
            return;
        }

        try {
            foreach (Schema::getTables() as $tableInfo) {
                $table = is_array($tableInfo)
                    ? ($tableInfo['name'] ?? null)
                    : data_get($tableInfo, 'name');

                if (! is_string($table)) {
                    continue;
                }

                $lower = strtolower($table);

                if (! str_contains($lower, 'pdf') && ! str_contains($lower, 'document')) {
                    continue;
                }

                $columns = Schema::getColumnListing($table);

                if (! in_array('consent_session_id', $columns, true)) {
                    continue;
                }

                $row = DB::table($table)
                    ->where('consent_session_id', $session->getKey())
                    ->orderByDesc(in_array('id', $columns, true) ? 'id' : $columns[0])
                    ->first();

                if ($row) {
                    yield $row;
                }
            }
        } catch (Throwable) {
            return;
        }
    }

    /**
     * @return array{data:string,source:string}|null
     */
    private function resolveCandidate(mixed $candidate): ?array
    {
        if (is_string($candidate)) {
            if (str_starts_with($candidate, '%PDF')) {
                return ['data' => $candidate, 'source' => 'generated PDF data'];
            }

            return $this->readPath($candidate);
        }

        if (is_array($candidate) || is_object($candidate)) {
            foreach ([
                'data',
                'contents',
                'content',
                'pdf_data',
                'pdf_content',
                'path',
                'file_path',
                'storage_path',
                'pdf_path',
                'disk_path',
            ] as $key) {
                $value = data_get($candidate, $key);

                if (! is_string($value) || $value === '') {
                    continue;
                }

                $resolved = $this->resolveCandidate($value);

                if ($resolved) {
                    return $resolved;
                }
            }
        }

        return null;
    }

    /**
     * @return array{data:string,source:string}|null
     */
    private function readPath(string $path): ?array
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (is_file($path)) {
            $data = file_get_contents($path);

            if (is_string($data) && str_starts_with($data, '%PDF')) {
                return ['data' => $data, 'source' => $path];
            }
        }

        $relativeCandidates = array_unique(array_filter([
            ltrim($path, '/'),
            preg_replace('#^storage/app/(?:private/|public/)?#', '', $path),
            preg_replace('#^'.preg_quote(storage_path('app'), '#').'/(?:private/|public/)?#', '', $path),
        ]));

        foreach (array_keys((array) config('filesystems.disks', [])) as $disk) {
            try {
                foreach ($relativeCandidates as $relative) {
                    if (! is_string($relative) || $relative === '') {
                        continue;
                    }

                    if (! Storage::disk($disk)->exists($relative)) {
                        continue;
                    }

                    $data = Storage::disk($disk)->get($relative);

                    if (is_string($data) && str_starts_with($data, '%PDF')) {
                        return [
                            'data' => $data,
                            'source' => $disk.':'.$relative,
                        ];
                    }
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function findRecentStoredPdf(ConsentSession $session): ?string
    {
        $root = storage_path('app');

        if (! is_dir($root)) {
            return null;
        }

        $minutes = max(
            5,
            (int) config(
                'signed-consent-delivery.attachment_search_minutes',
                120
            )
        );
        $cutoff = now()->subMinutes($minutes)->getTimestamp();
        $needles = array_filter([
            (string) $session->getKey(),
            (string) $session->access_token,
        ]);
        $matches = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $root,
                    \FilesystemIterator::SKIP_DOTS
                )
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                if (strtolower($file->getExtension()) !== 'pdf') {
                    continue;
                }

                if ($file->getMTime() < $cutoff) {
                    continue;
                }

                $path = $file->getPathname();
                $basename = strtolower($file->getBasename());
                $matched = false;

                foreach ($needles as $needle) {
                    if ($needle !== '' && str_contains($basename, strtolower($needle))) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    $matches[$path] = $file->getMTime();
                }
            }
        } catch (Throwable) {
            return null;
        }

        if ($matches === []) {
            return null;
        }

        arsort($matches);

        return array_key_first($matches);
    }

    private function attachmentName(ConsentSession $session): string
    {
        $title = Str::slug(
            $session->consentTemplate?->title ?? 'signed-consent'
        );
        $signer = Str::slug($session->signer_name ?? 'signer');

        return trim($title.'-'.$signer.'-'.$session->getKey(), '-').'.pdf';
    }
}
