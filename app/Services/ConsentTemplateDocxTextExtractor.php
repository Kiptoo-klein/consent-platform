<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use Throwable;
use ZipArchive;

class ConsentTemplateDocxTextExtractor
{
    private const MAX_ARCHIVE_ENTRIES = 2000;
    private const MAX_UNCOMPRESSED_BYTES = 50 * 1024 * 1024;
    private const MAX_EXTRACTED_CHARACTERS = 200000;

    /** @var array<int, string> */
    private array $warnings = [];

    /**
     * @return array{
     *     text: string,
     *     html: string,
     *     warnings: array<int, string>
     * }
     */
    public function extract(string $path): array
    {
        $this->warnings = [];
        $this->assertSafeArchive($path);

        try {
            $phpWord = IOFactory::load(
                $path,
                'Word2007'
            );

            $writer = IOFactory::createWriter(
                $phpWord,
                'HTML'
            );

            if (method_exists(
                $writer,
                'setDefaultWhiteSpace'
            )) {
                $writer->setDefaultWhiteSpace(
                    'normal'
                );
            }

            $documentHtml = $writer->getContent();
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'The Word document could not be read. Confirm that it is a valid, unprotected .docx file.',
                previous: $exception
            );
        }

        $html = $this->bodyHtml(
            $documentHtml
        );

        $text = $this->plainText(
            $html
        );

        if (
            $text === ''
            && ! str_contains(
                strtolower($html),
                '<img'
            )
        ) {
            throw new RuntimeException(
                'No readable consent text or images were found in the Word document.'
            );
        }

        if (mb_strlen($text) > self::MAX_EXTRACTED_CHARACTERS) {
            throw new RuntimeException(
                'The imported document contains too much text. Reduce it to fewer than 200,000 characters and try again.'
            );
        }

        $this->warnings[] =
            'Review the imported document before saving. Word page breaks, columns, floating objects, headers and footers may not match the original layout exactly.';

        return [
            'text' => $text,
            'html' => $html,
            'warnings' =>
                array_values(
                    array_unique(
                        $this->warnings
                    )
                ),
        ];
    }

    private function assertSafeArchive(
        string $path
    ): void {
        $zip = new ZipArchive();
        $opened = $zip->open($path);

        if ($opened !== true) {
            throw new RuntimeException(
                'The uploaded file is not a readable .docx archive.'
            );
        }

        try {
            if (
                $zip->locateName(
                    '[Content_Types].xml'
                ) === false
                || $zip->locateName(
                    'word/document.xml'
                ) === false
            ) {
                throw new RuntimeException(
                    'The uploaded file is not a valid Word .docx document.'
                );
            }

            if (
                $zip->locateName(
                    'word/vbaProject.bin'
                ) !== false
            ) {
                throw new RuntimeException(
                    'Macro-enabled Word documents are not supported.'
                );
            }

            if (
                $zip->numFiles
                > self::MAX_ARCHIVE_ENTRIES
            ) {
                throw new RuntimeException(
                    'The Word document contains too many embedded files.'
                );
            }

            $uncompressedBytes = 0;

            for (
                $index = 0;
                $index < $zip->numFiles;
                $index++
            ) {
                $stat = $zip->statIndex($index);

                if (! is_array($stat)) {
                    continue;
                }

                $uncompressedBytes +=
                    (int) ($stat['size'] ?? 0);

                if (
                    $uncompressedBytes
                    > self::MAX_UNCOMPRESSED_BYTES
                ) {
                    throw new RuntimeException(
                        'The Word document expands beyond the safe import size.'
                    );
                }

                $name =
                    (string) ($stat['name'] ?? '');

                if (
                    str_starts_with(
                        $name,
                        'word/header'
                    )
                    || str_starts_with(
                        $name,
                        'word/footer'
                    )
                ) {
                    $this->warnings[] =
                        'Word headers and footers are not imported into the consent body.';
                }

                if (
                    str_starts_with(
                        $name,
                        'word/embeddings/'
                    )
                ) {
                    $this->warnings[] =
                        'Embedded Office objects are not imported.';
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function bodyHtml(
        string $documentHtml
    ): string {
        $document = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $previous = libxml_use_internal_errors(
            true
        );

        try {
            $document->loadHTML(
                '<?xml encoding="UTF-8">'
                .$documentHtml,
                LIBXML_NONET
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors(
                $previous
            );
        }

        $xpath = new DOMXPath($document);
        $body = $xpath->query('//body')
            ?->item(0);

        if (! $body instanceof DOMElement) {
            throw new RuntimeException(
                'The converted Word document body could not be read.'
            );
        }

        $html = '';

        foreach ($body->childNodes as $child) {
            $html .= $document->saveHTML($child)
                ?: '';
        }

        return trim($html);
    }

    private function plainText(
        string $html
    ): string {
        $html = preg_replace(
            '/<br\s*\/?>/i',
            "\n",
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<\/(?:p|div|h[1-6]|li|tr|table|ul|ol)>/i',
            "\n",
            $html
        ) ?? $html;

        $text = html_entity_decode(
            strip_tags($html),
            ENT_QUOTES
            | ENT_HTML5
            | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $text
        );

        $text = preg_replace(
            '/\n{3,}/',
            "\n\n",
            $text
        ) ?? $text;

        return trim($text);
    }
}
