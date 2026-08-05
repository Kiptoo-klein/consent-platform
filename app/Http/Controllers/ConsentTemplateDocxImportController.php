<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\ConsentTemplateContentService;
use App\Services\ConsentTemplateDocxTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ConsentTemplateDocxImportController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function __invoke(
        Request $request,
        ConsentTemplateDocxTextExtractor $extractor,
        ConsentTemplateContentService $contentService
    ): JsonResponse {
        $validated = $request->validate([
            'document' => [
                'required',
                'file',
                'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/octet-stream',
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
            strtolower(
                $document->getClientOriginalExtension()
            ) !== 'docx'
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

        $organizationId = (int) $request
            ->user()
            ->organization_id;

        try {
            $result = $extractor->extract(
                $path
            );

            $prepared = $contentService
                ->prepareImportedHtml(
                    $result['html'],
                    $organizationId
                );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'document' =>
                    $exception->getMessage(),
            ]);
        }

        $originalName =
            $document->getClientOriginalName();

        $suggestedTitle = Str::of(
            pathinfo(
                $originalName,
                PATHINFO_FILENAME
            )
        )
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->toString();

        $this->activityLogger->log(
            action:
                'consent_template.docx_imported',
            description:
                'Imported formatted consent content and images from a Word document.',
            organizationId: $organizationId,
            properties: [
                'original_filename' =>
                    $originalName,
                'file_size_bytes' =>
                    $document->getSize(),
                'character_count' =>
                    mb_strlen(
                        $prepared['text']
                    ),
                'image_count' =>
                    $prepared['image_count'],
                'warnings' =>
                    $result['warnings'],
            ],
        );

        return response()->json([
            'content' => $prepared['html'],
            'plain_text' => $prepared['text'],
            'suggested_title' =>
                $suggestedTitle,
            'filename' => $originalName,
            'character_count' =>
                mb_strlen(
                    $prepared['text']
                ),
            'image_count' =>
                $prepared['image_count'],
            'warnings' =>
                $result['warnings'],
        ]);
    }
}
