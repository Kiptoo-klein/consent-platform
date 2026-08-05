<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ConsentTemplateAssetService
{
    /**
     * Return the configured persistent public asset disk.
     */
    public function diskName(): string
    {
        return (string) config(
            'consent-template-assets.disk',
            'public'
        );
    }

    /**
     * Store one directly uploaded editor image.
     *
     * @return array{
     *     url: string,
     *     path: string,
     *     disk: string,
     *     mime: string,
     *     width: int,
     *     height: int
     * }
     */
    public function storeUploadedImage(
        UploadedFile $image,
        int $organizationId
    ): array {
        $realPath = $image->getRealPath();

        if (! is_string($realPath) || $realPath === '') {
            throw new RuntimeException(
                'The uploaded image could not be read.'
            );
        }

        $bytes = file_get_contents($realPath);

        if (! is_string($bytes)) {
            throw new RuntimeException(
                'The uploaded image could not be read.'
            );
        }

        return $this->storeImageBytes(
            $bytes,
            $organizationId
        );
    }

    /**
     * Extract embedded data-URI images from imported Word HTML.
     *
     * @return array{
     *     html: string,
     *     image_count: int,
     *     stored_paths: array<int, string>
     * }
     */
    public function persistEmbeddedImages(
        string $html,
        int $organizationId
    ): array {
        [$document, $root] = $this->loadFragment($html);

        $images = [];

        foreach ($root->getElementsByTagName('img') as $image) {
            if ($image instanceof DOMElement) {
                $images[] = $image;
            }
        }

        $limit = (int) config(
            'consent-template-assets.max_import_images',
            20
        );

        if (count($images) > $limit) {
            throw new RuntimeException(
                "The Word document contains more than {$limit} images."
            );
        }

        $storedPaths = [];
        $totalBytes = 0;
        $totalLimit = (int) config(
            'consent-template-assets.max_import_image_bytes',
            20 * 1024 * 1024
        );

        try {
            foreach ($images as $image) {
                $src = trim($image->getAttribute('src'));

                if (! str_starts_with($src, 'data:image/')) {
                    continue;
                }

                $decoded = $this->decodeDataImage($src);
                $totalBytes += strlen($decoded['bytes']);

                if ($totalBytes > $totalLimit) {
                    throw new RuntimeException(
                        'The embedded images exceed the safe import size.'
                    );
                }

                $stored = $this->storeImageBytes(
                    $decoded['bytes'],
                    $organizationId
                );

                $storedPaths[] = $stored['path'];

                $image->setAttribute('src', $stored['url']);
                $image->setAttribute(
                    'data-consent-asset-path',
                    $stored['path']
                );
                $image->setAttribute(
                    'data-consent-asset-disk',
                    $stored['disk']
                );
                $image->setAttribute(
                    'width',
                    (string) $stored['width']
                );
                $image->setAttribute(
                    'height',
                    (string) $stored['height']
                );
            }
        } catch (Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                Storage::disk($this->diskName())
                    ->delete($storedPath);
            }

            throw $exception;
        }

        return [
            'html' => $this->innerHtml($root),
            'image_count' => count($storedPaths),
            'stored_paths' => $storedPaths,
        ];
    }

    /**
     * Validate a stored image reference and regenerate its public URL.
     */
    public function normalizeStoredImage(
        DOMElement $image,
        int $organizationId
    ): bool {
        $disk = trim(
            $image->getAttribute(
                'data-consent-asset-disk'
            )
        );

        $path = trim(
            $image->getAttribute(
                'data-consent-asset-path'
            )
        );

        if (
            $disk !== $this->diskName()
            || ! $this->validOrganizationPath(
                $path,
                $organizationId
            )
            || ! Storage::disk($disk)->exists($path)
        ) {
            return false;
        }

        $url = Storage::disk($disk)->url($path);

        /*
         * Local public assets should use the current browser origin.
         * This prevents localhost, 127.0.0.1 and LAN-address mismatches
         * while retaining full object-storage URLs in production.
         */
        if (
            $disk === 'public'
            && config("filesystems.disks.{$disk}.driver") === 'local'
        ) {
            $url = '/storage/'.ltrim($path, '/');
        }

        $image->setAttribute('src', $url);

        return true;
    }

    /**
     * Delete newly stored paths after an import fails validation.
     *
     * @param array<int, string> $paths
     */
    public function deleteStoredPaths(
        array $paths,
        int $organizationId
    ): void {
        $disk = $this->diskName();

        foreach ($paths as $path) {
            if (
                is_string($path)
                && $this->validOrganizationPath(
                    $path,
                    $organizationId
                )
            ) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    /**
     * Replace stored asset URLs with data URIs for Dompdf.
     */
    public function embedImagesForPdf(
        string $html,
        int $organizationId
    ): string {
        [$document, $root] = $this->loadFragment($html);

        $images = [];

        foreach ($root->getElementsByTagName('img') as $image) {
            if ($image instanceof DOMElement) {
                $images[] = $image;
            }
        }

        foreach ($images as $image) {
            $disk = trim(
                $image->getAttribute(
                    'data-consent-asset-disk'
                )
            );

            $path = trim(
                $image->getAttribute(
                    'data-consent-asset-path'
                )
            );

            if (
                $disk !== $this->diskName()
                || ! $this->validOrganizationPath(
                    $path,
                    $organizationId
                )
                || ! Storage::disk($disk)->exists($path)
            ) {
                $image->parentNode?->removeChild($image);
                continue;
            }

            $bytes = Storage::disk($disk)->get($path);
            $info = @getimagesizefromstring($bytes);
            $mime = is_array($info)
                ? (string) ($info['mime'] ?? '')
                : '';

            if (! in_array(
                $mime,
                [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ],
                true
            )) {
                $image->parentNode?->removeChild($image);
                continue;
            }

            $image->setAttribute(
                'src',
                'data:'.$mime.';base64,'.base64_encode($bytes)
            );

            $image->removeAttribute(
                'data-consent-asset-path'
            );
            $image->removeAttribute(
                'data-consent-asset-disk'
            );
        }

        return $this->innerHtml($root);
    }

    /**
     * @return array{bytes: string, mime: string}
     */
    private function decodeDataImage(string $src): array
    {
        if (! preg_match(
            '/^data:(image\/(?:jpeg|png|webp));base64,([A-Za-z0-9+\/=\r\n]+)$/',
            $src,
            $matches
        )) {
            throw new RuntimeException(
                'An embedded image uses an unsupported format.'
            );
        }

        $bytes = base64_decode(
            preg_replace('/\s+/', '', $matches[2]) ?? '',
            true
        );

        if (! is_string($bytes)) {
            throw new RuntimeException(
                'An embedded image could not be decoded.'
            );
        }

        return [
            'bytes' => $bytes,
            'mime' => $matches[1],
        ];
    }

    /**
     * @return array{
     *     url: string,
     *     path: string,
     *     disk: string,
     *     mime: string,
     *     width: int,
     *     height: int
     * }
     */
    private function storeImageBytes(
        string $bytes,
        int $organizationId
    ): array {
        $maxBytes = (int) config(
            'consent-template-assets.max_image_bytes',
            5 * 1024 * 1024
        );

        if (
            strlen($bytes) === 0
            || strlen($bytes) > $maxBytes
        ) {
            throw new RuntimeException(
                'Each consent document image must not exceed 5 MB.'
            );
        }

        $info = @getimagesizefromstring($bytes);

        if (! is_array($info)) {
            throw new RuntimeException(
                'The uploaded file is not a readable image.'
            );
        }

        $mime = (string) ($info['mime'] ?? '');

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };

        if ($extension === null) {
            throw new RuntimeException(
                'Only JPG, PNG and WEBP images are supported.'
            );
        }

        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);

        if (
            $width < 1
            || $height < 1
            || $width > 6000
            || $height > 6000
        ) {
            throw new RuntimeException(
                'Images must be no larger than 6000 by 6000 pixels.'
            );
        }

        $disk = $this->diskName();
        $path = sprintf(
            'consent-template-assets/%d/%s.%s',
            $organizationId,
            (string) Str::uuid(),
            $extension
        );

        $stored = Storage::disk($disk)->put(
            $path,
            $bytes,
            [
                'visibility' => 'public',
                'ContentType' => $mime,
            ]
        );

        if (! $stored) {
            throw new RuntimeException(
                'The consent document image could not be stored.'
            );
        }

        return [
            'url' => Storage::disk($disk)->url($path),
            'path' => $path,
            'disk' => $disk,
            'mime' => $mime,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function validOrganizationPath(
        string $path,
        int $organizationId
    ): bool {
        return preg_match(
            '#^consent-template-assets/'
            .preg_quote((string) $organizationId, '#')
            .'/[0-9a-fA-F-]{36}\.(?:jpg|png|webp)$#',
            $path
        ) === 1;
    }

    /**
     * @return array{DOMDocument, DOMElement}
     */
    private function loadFragment(string $html): array
    {
        $document = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML(
                '<?xml encoding="UTF-8">'
                .'<div id="consent-template-root">'
                .$html
                .'</div>',
                LIBXML_HTML_NOIMPLIED
                | LIBXML_HTML_NODEFDTD
                | LIBXML_NONET
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $root = $xpath->query(
            '//*[@id="consent-template-root"]'
        )?->item(0);

        if (! $root instanceof DOMElement) {
            throw new RuntimeException(
                'The consent document HTML could not be processed.'
            );
        }

        return [$document, $root];
    }

    private function innerHtml(DOMElement $root): string
    {
        $html = '';

        foreach ($root->childNodes as $child) {
            $html .= $root->ownerDocument?->saveHTML($child)
                ?: '';
        }

        return trim($html);
    }
}
