<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\ConsentTemplateDocxTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ConsentTemplateDocxImportController extends Controller
{
    public function __construct(
        protected ActivityLogger $activityLogger
    ) {
    }

    public function __invoke(
        Request $request,
        ConsentTemplateDocxTextExtractor $extractor
    ): JsonResponse {
        $validated = $request->validate([
            'document' => [
                'required',
                'file',
                'max:10240',
            ],
        ], [
            'document.required' =>
                'Select a Word .docx document to import.',
            'document.file' =>
                'The selected upload is not a valid file.',
            'document.max' =>
                'The Word document must not exceed 10 MB.',
        ]);

        /** @var UploadedFile $document */
        $document = $validated['document'];

        if (
            strtolower($document->getClientOriginalExtension())
            !== 'docx'
        ) {
            throw ValidationException::withMessages([
                'document' =>
                    'Only Microsoft Word .docx files are supported.',
            ]);
        }

        $path = $document->getRealPath();

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                'document' =>
                    'The uploaded Word document could not be read.',
            ]);
        }

        try {
            $result = $extractor->extract($path);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'document' => $exception->getMessage(),
            ]);
        }

        $originalName = $document->getClientOriginalName();

        $suggestedTitle = Str::of(
            pathinfo($originalName, PATHINFO_FILENAME)
        )
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->toString();

        $organizationId = (int) Auth::user()->organization_id;

        $this->activityLogger->log(
            action: 'consent_template.docx_imported',
            description:
                'Imported consent text from a Word document.',
            organizationId: $organizationId,
            properties: [
                'original_filename' => $originalName,
                'file_size_bytes' => $document->getSize(),
                'character_count' =>
                    mb_strlen($result['text']),
                'warnings' => $result['warnings'],
            ],
        );

        return response()->json([
            'content' => $result['text'],
            'suggested_title' => $suggestedTitle,
            'filename' => $originalName,
            'character_count' =>
                mb_strlen($result['text']),
            'warnings' => $result['warnings'],
        ]);
    }
}
