<?php

namespace App\Services;

use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\OLEObject;
use PhpOffice\PhpWord\Element\PageBreak;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextBreak;
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
     * @return array{text: string, warnings: array<int, string>}
     */
    public function extract(string $path): array
    {
        $this->warnings = [];
        $this->assertSafeArchive($path);

        try {
            $phpWord = IOFactory::load($path, 'Word2007');
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'The Word document could not be read. Confirm that it is a valid, unprotected .docx file.',
                previous: $exception
            );
        }

        $blocks = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $block = $this->blockText($element);

                if (trim($block) !== '') {
                    $blocks[] = $block;
                }
            }
        }

        $text = $this->normalizeText(implode("\n\n", $blocks));

        if ($text === '') {
            throw new RuntimeException(
                'No readable consent text was found in the Word document.'
            );
        }

        if (mb_strlen($text) > self::MAX_EXTRACTED_CHARACTERS) {
            throw new RuntimeException(
                'The imported document contains too much text. Reduce it to fewer than 200,000 characters and try again.'
            );
        }

        $this->warnings[] =
            'Review the imported text before saving. Fonts, colours, images, columns, headers, footers, and exact page layout are not preserved.';

        return [
            'text' => $text,
            'warnings' => array_values(array_unique($this->warnings)),
        ];
    }

    private function assertSafeArchive(string $path): void
    {
        $zip = new ZipArchive();
        $opened = $zip->open($path);

        if ($opened !== true) {
            throw new RuntimeException(
                'The uploaded file is not a readable .docx archive.'
            );
        }

        try {
            if (
                $zip->locateName('[Content_Types].xml') === false
                || $zip->locateName('word/document.xml') === false
            ) {
                throw new RuntimeException(
                    'The uploaded file is not a valid Word .docx document.'
                );
            }

            if ($zip->locateName('word/vbaProject.bin') !== false) {
                throw new RuntimeException(
                    'Macro-enabled Word documents are not supported.'
                );
            }

            if ($zip->numFiles > self::MAX_ARCHIVE_ENTRIES) {
                throw new RuntimeException(
                    'The Word document contains too many embedded files.'
                );
            }

            $uncompressedBytes = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);

                if (! is_array($stat)) {
                    continue;
                }

                $uncompressedBytes += (int) ($stat['size'] ?? 0);

                if ($uncompressedBytes > self::MAX_UNCOMPRESSED_BYTES) {
                    throw new RuntimeException(
                        'The Word document expands beyond the safe import size.'
                    );
                }

                $name = (string) ($stat['name'] ?? '');

                if (str_starts_with($name, 'word/media/')) {
                    $this->warnings[] =
                        'Images in the Word document were not imported.';
                }

                if (
                    str_starts_with($name, 'word/header')
                    || str_starts_with($name, 'word/footer')
                ) {
                    $this->warnings[] =
                        'Word headers and footers were not imported.';
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function blockText(mixed $element): string
    {
        if ($element instanceof Table) {
            return $this->tableText($element);
        }

        if (
            $element instanceof ListItem
            || $element instanceof ListItemRun
        ) {
            $text = trim($this->inlineText($element));

            return $text === '' ? '' : '• '.$text;
        }

        if (
            $element instanceof PageBreak
            || $element instanceof TextBreak
        ) {
            return "\n";
        }

        if (
            $element instanceof Image
            || $element instanceof OLEObject
        ) {
            $this->warnings[] =
                'Embedded images and objects were not imported.';

            return '';
        }

        return trim($this->inlineText($element));
    }

    private function inlineText(mixed $element): string
    {
        if ($element === null) {
            return '';
        }

        if (is_scalar($element)) {
            return (string) $element;
        }

        if ($element instanceof TextBreak) {
            return "\n";
        }

        if ($element instanceof PageBreak) {
            return "\n\n";
        }

        if (
            $element instanceof Image
            || $element instanceof OLEObject
        ) {
            $this->warnings[] =
                'Embedded images and objects were not imported.';

            return '';
        }

        if ($element instanceof Table) {
            return $this->tableText($element);
        }

        if (
            is_object($element)
            && method_exists($element, 'getTextObject')
        ) {
            return $this->inlineText($element->getTextObject());
        }

        if (
            is_object($element)
            && method_exists($element, 'getElements')
        ) {
            $parts = [];

            foreach ($element->getElements() as $child) {
                $parts[] = $this->inlineText($child);
            }

            return implode('', $parts);
        }

        if (
            is_object($element)
            && method_exists($element, 'getText')
        ) {
            return $this->inlineText($element->getText());
        }

        return '';
    }

    private function tableText(Table $table): string
    {
        $rows = [];

        foreach ($table->getRows() as $row) {
            $cells = [];

            foreach ($row->getCells() as $cell) {
                $parts = [];

                foreach ($cell->getElements() as $element) {
                    $text = trim($this->blockText($element));

                    if ($text !== '') {
                        $parts[] = $text;
                    }
                }

                $cells[] = implode(' / ', $parts);
            }

            $rowText = trim(implode(' | ', $cells));

            if ($rowText !== '') {
                $rows[] = $rowText;
            }
        }

        return implode("\n", $rows);
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $text = preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
            '',
            $text
        ) ?? $text;

        $lines = array_map(
            static fn (string $line): string => rtrim($line),
            explode("\n", $text)
        );

        $text = implode("\n", $lines);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
