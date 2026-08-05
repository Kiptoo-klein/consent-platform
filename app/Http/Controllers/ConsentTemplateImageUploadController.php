<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\ConsentTemplateAssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class ConsentTemplateImageUploadController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function __invoke(
        Request $request,
        ConsentTemplateAssetService $assets
    ): JsonResponse {
        $validated = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:max_width=6000,max_height=6000',
            ],
        ], [
            'image.required' =>
                'Select an image to insert.',
            'image.image' =>
                'The selected file is not a valid image.',
            'image.mimes' =>
                'Only JPG, PNG and WEBP images are supported.',
            'image.max' =>
                'The image must not exceed 5 MB.',
            'image.dimensions' =>
                'The image must not exceed 6000 by 6000 pixels.',
        ]);

        /** @var UploadedFile $image */
        $image = $validated['image'];
        $organizationId = (int) $request
            ->user()
            ->organization_id;

        try {
            $stored = $assets->storeUploadedImage(
                $image,
                $organizationId
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $this->activityLogger->log(
            action: 'consent_template.image_uploaded',
            description:
                'Uploaded an image for a consent template.',
            organizationId: $organizationId,
            properties: [
                'original_filename' =>
                    $image->getClientOriginalName(),
                'asset_path' => $stored['path'],
                'mime_type' => $stored['mime'],
                'width' => $stored['width'],
                'height' => $stored['height'],
                'file_size_bytes' => $image->getSize(),
            ],
        );

        return response()->json([
            'url' => $stored['url'],
            'asset_path' => $stored['path'],
            'asset_disk' => $stored['disk'],
            'width' => $stored['width'],
            'height' => $stored['height'],
            'alt' => pathinfo(
                $image->getClientOriginalName(),
                PATHINFO_FILENAME
            ),
        ]);
    }
}
