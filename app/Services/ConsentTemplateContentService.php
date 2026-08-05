<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ConsentTemplateContentService
{
    private const MAX_EDITOR_HTML_BYTES = 1000000;

    private const ALLOWED_TAGS = [
        'p',
        'div',
        'span',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        's',
        'strike',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'ul',
        'ol',
        'li',
        'blockquote',
        'a',
        'img',
        'table',
        'thead',
        'tbody',
        'tfoot',
        'tr',
        'th',
        'td',
        'hr',
        'figure',
        'figcaption',
    ];

    private const DROP_TAGS = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'form',
        'input',
        'button',
        'textarea',
        'select',
        'option',
        'svg',
        'math',
        'meta',
        'link',
        'base',
    ];

    public function __construct(
        private readonly ConsentTemplateAssetService $assets
    ) {
    }

    /**
     * Sanitize submitted editor HTML and derive a plain-text fallback.
     *
     * @return array{html: string, text: string}
     */
    public function prepare(
        string $content,
        int $organizationId
    ): array {
        if (
            strlen($content)
            > self::MAX_EDITOR_HTML_BYTES
        ) {
            throw ValidationException::withMessages([
                'content' =>
                    'The consent document is too large. Reduce its text or formatting and try again.',
            ]);
        }

        $html = $this->looksLikeHtml($content)
            ? $content
            : $this->plainTextToHtml($content);

        $html = $this->sanitize(
            $html,
            $organizationId
        );

        if (! $this->hasMeaningfulContent($html)) {
            throw ValidationException::withMessages([
                'content' =>
                    'Enter consent text or add at least one document image.',
            ]);
        }

        return [
            'html' => $html,
            'text' => $this->plainTextFromHtml($html),
        ];
    }

    /**
     * Persist embedded Word images before sanitizing imported HTML.
     *
     * @return array{
     *     html: string,
     *     text: string,
     *     image_count: int
     * }
     */
    public function prepareImportedHtml(
        string $html,
        int $organizationId
    ): array {
        $persisted = $this->assets
            ->persistEmbeddedImages(
                $html,
                $organizationId
            );

        try {
            $prepared = $this->prepare(
                $persisted['html'],
                $organizationId
            );
        } catch (ValidationException $exception) {
            $this->assets->deleteStoredPaths(
                $persisted['stored_paths'],
                $organizationId
            );

            $message = collect(
                $exception->errors()
            )->flatten()->first()
                ?: 'The imported Word document could not be prepared.';

            throw new RuntimeException(
                (string) $message,
                previous: $exception
            );
        } catch (Throwable $exception) {
            $this->assets->deleteStoredPaths(
                $persisted['stored_paths'],
                $organizationId
            );

            throw $exception;
        }

        return [
            ...$prepared,
            'image_count' =>
                $persisted['image_count'],
        ];
    }

    /**
     * Render stored HTML safely, with plain-text legacy fallback.
     */
    public function render(
        ?string $html,
        ?string $text,
        int $organizationId
    ): string {
        $source = filled($html)
            ? (string) $html
            : $this->plainTextToHtml(
                (string) $text
            );

        return $this->sanitize(
            $source,
            $organizationId
        );
    }

    /**
     * Produce sanitized Dompdf-ready HTML with embedded image bytes.
     */
    public function forPdf(
        ?string $html,
        ?string $text,
        int $organizationId
    ): string {
        return $this->assets->embedImagesForPdf(
            $this->render(
                $html,
                $text,
                $organizationId
            ),
            $organizationId
        );
    }

    private function sanitize(
        string $html,
        int $organizationId
    ): string {
        [$document, $root] = $this->loadFragment(
            $this->convertFontElements($html)
        );

        $this->sanitizeChildren(
            $root,
            $organizationId
        );

        return $this->innerHtml($root);
    }

    private function sanitizeChildren(
        DOMNode $parent,
        int $organizationId
    ): void {
        $children = [];

        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($child);
                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_TAGS, true)) {
                $parent->removeChild($child);
                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->sanitizeChildren(
                    $child,
                    $organizationId
                );

                while ($child->firstChild !== null) {
                    $parent->insertBefore(
                        $child->firstChild,
                        $child
                    );
                }

                $parent->removeChild($child);
                continue;
            }

            $this->sanitizeAttributes(
                $child,
                $tag
            );

            if (
                $tag === 'img'
                && ! $this->assets
                    ->normalizeStoredImage(
                        $child,
                        $organizationId
                    )
            ) {
                $parent->removeChild($child);
                continue;
            }

            $this->sanitizeChildren(
                $child,
                $organizationId
            );
        }
    }

    private function sanitizeAttributes(
        DOMElement $element,
        string $tag
    ): void {
        $original = [];

        foreach ($element->attributes as $attribute) {
            $original[strtolower($attribute->name)] =
                $attribute->value;
        }

        foreach (array_keys($original) as $attributeName) {
            $element->removeAttribute($attributeName);
        }

        $style = $this->sanitizeStyle(
            $original['style'] ?? '',
            $tag
        );

        if ($style !== '') {
            $element->setAttribute('style', $style);
        }

        if ($tag === 'a') {
            $href = trim($original['href'] ?? '');

            if ($this->safeLink($href)) {
                $element->setAttribute('href', $href);
                $element->setAttribute(
                    'rel',
                    'noopener noreferrer'
                );

                if (
                    ($original['target'] ?? '')
                    === '_blank'
                ) {
                    $element->setAttribute(
                        'target',
                        '_blank'
                    );
                }
            }

            $title = trim($original['title'] ?? '');

            if ($title !== '') {
                $element->setAttribute(
                    'title',
                    mb_substr($title, 0, 255)
                );
            }
        }

        if ($tag === 'img') {
            foreach ([
                'data-consent-asset-path',
                'data-consent-asset-disk',
            ] as $name) {
                if (isset($original[$name])) {
                    $element->setAttribute(
                        $name,
                        $original[$name]
                    );
                }
            }

            $alt = trim($original['alt'] ?? '');

            if ($alt !== '') {
                $element->setAttribute(
                    'alt',
                    mb_substr($alt, 0, 255)
                );
            }

            foreach (['width', 'height'] as $dimension) {
                $value = $original[$dimension] ?? '';

                if (
                    preg_match(
                        '/^[1-9][0-9]{0,3}$/',
                        $value
                    ) === 1
                ) {
                    $element->setAttribute(
                        $dimension,
                        $value
                    );
                }
            }
        }

        if (in_array(
            $tag,
            ['td', 'th'],
            true
        )) {
            foreach (['colspan', 'rowspan'] as $span) {
                $value = $original[$span] ?? '';

                if (
                    preg_match(
                        '/^[1-9][0-9]?$/',
                        $value
                    ) === 1
                ) {
                    $element->setAttribute(
                        $span,
                        $value
                    );
                }
            }
        }
    }

    private function sanitizeStyle(
        string $style,
        string $tag
    ): string {
        $allowed = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map(
                'trim',
                explode(':', $declaration, 2)
            );

            $property = strtolower($property);

            if (
                $value === ''
                || preg_match(
                    '/url\s*\(|expression\s*\(|javascript:|[<>\\\\]/i',
                    $value
                )
            ) {
                continue;
            }

            $valid = match ($property) {
                'font-size' =>
                    preg_match(
                        '/^(?:[8-9]|[1-6][0-9]|7[0-2])(?:px|pt)$/i',
                        $value
                    ) === 1,

                'font-family' =>
                    mb_strlen($value) <= 100
                    && preg_match(
                        '/^[A-Za-z0-9 ,"\x27-]+$/',
                        $value
                    ) === 1,

                'font-weight' =>
                    preg_match(
                        '/^(?:normal|bold|[1-9]00)$/i',
                        $value
                    ) === 1,

                'font-style' =>
                    preg_match(
                        '/^(?:normal|italic)$/i',
                        $value
                    ) === 1,

                'text-decoration' =>
                    preg_match(
                        '/^(?:none|underline|line-through|underline line-through)$/i',
                        $value
                    ) === 1,

                'text-align' =>
                    preg_match(
                        '/^(?:left|center|right|justify)$/i',
                        $value
                    ) === 1,

                'color',
                'background',
                'background-color' =>
                    $this->safeColor($value),

                'margin-left',
                'margin-right',
                'padding-left',
                'padding-right' =>
                    preg_match(
                        '/^[0-9]{1,3}(?:\.[0-9]+)?(?:px|pt)$/i',
                        $value
                    ) === 1,

                'width',
                'max-width',
                'height' =>
                    $tag === 'img'
                    && preg_match(
                        '/^(?:auto|[1-9][0-9]{0,3}(?:px|pt|%))$/i',
                        $value
                    ) === 1,

                'vertical-align' =>
                    preg_match(
                        '/^(?:top|middle|bottom|baseline)$/i',
                        $value
                    ) === 1,

                'border-collapse' =>
                    $tag === 'table'
                    && strtolower($value) === 'collapse',

                default => false,
            };

            if ($valid) {
                $allowed[$property] = $value;
            }
        }

        return implode(
            '; ',
            array_map(
                static fn (
                    string $property,
                    string $value
                ): string => "{$property}: {$value}",
                array_keys($allowed),
                array_values($allowed)
            )
        );
    }

    private function safeColor(string $value): bool
    {
        return preg_match(
            '/^#[0-9A-Fa-f]{3}(?:[0-9A-Fa-f]{3})?$/',
            $value
        ) === 1
            || preg_match(
                '/^rgb\(\s*(?:[0-9]{1,3}\s*,\s*){2}[0-9]{1,3}\s*\)$/i',
                $value
            ) === 1;
    }

    private function safeLink(string $href): bool
    {
        if ($href === '') {
            return false;
        }

        return preg_match(
            '/^(?:https?:\/\/|mailto:|tel:|#)/i',
            $href
        ) === 1;
    }

    private function convertFontElements(
        string $html
    ): string {
        return preg_replace_callback(
            '/<font\b([^>]*)>(.*?)<\/font>/is',
            function (array $matches): string {
                $attributes = $matches[1];
                $styles = [];

                if (preg_match(
                    '/\bsize=["\x27]?([1-7])["\x27]?/i',
                    $attributes,
                    $size
                )) {
                    $pixels = [
                        1 => 10,
                        2 => 13,
                        3 => 16,
                        4 => 18,
                        5 => 24,
                        6 => 32,
                        7 => 48,
                    ];

                    $styles[] =
                        'font-size: '
                        .$pixels[(int) $size[1]]
                        .'px';
                }

                if (preg_match(
                    '/\bface=["\x27]([^"\x27]+)["\x27]/i',
                    $attributes,
                    $face
                )) {
                    $styles[] =
                        'font-family: '
                        .$face[1];
                }

                $styleAttribute = $styles === []
                    ? ''
                    : ' style="'
                        .htmlspecialchars(
                            implode('; ', $styles),
                            ENT_QUOTES
                            | ENT_SUBSTITUTE,
                            'UTF-8'
                        )
                        .'"';

                return '<span'
                    .$styleAttribute
                    .'>'
                    .$matches[2]
                    .'</span>';
            },
            $html
        ) ?? $html;
    }

    private function hasMeaningfulContent(
        string $html
    ): bool {
        if (str_contains($html, '<img')) {
            return true;
        }

        return trim(
            preg_replace(
                '/\x{00A0}/u',
                ' ',
                $this->plainTextFromHtml($html)
            ) ?? ''
        ) !== '';
    }

    private function looksLikeHtml(string $content): bool
    {
        return preg_match(
            '/<\/?[A-Za-z][^>]*>/',
            $content
        ) === 1;
    }

    private function plainTextToHtml(string $text): string
    {
        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $text
        );

        $paragraphs = preg_split(
            '/\n{2,}/',
            trim($text)
        ) ?: [];

        return implode(
            '',
            array_map(
                static function (string $paragraph): string {
                    return '<p>'
                        .nl2br(
                            htmlspecialchars(
                                $paragraph,
                                ENT_QUOTES
                                | ENT_SUBSTITUTE,
                                'UTF-8'
                            ),
                            false
                        )
                        .'</p>';
                },
                $paragraphs
            )
        );
    }

    private function plainTextFromHtml(string $html): string
    {
        $html = preg_replace(
            '/<(?:br)\s*\/?>/i',
            "\n",
            $html
        ) ?? $html;

        $html = preg_replace(
            '/<\/(?:p|div|h[1-6]|li|blockquote|tr|table|ul|ol)>/i',
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
            '/[ \t]+\n/',
            "\n",
            $text
        ) ?? $text;

        $text = preg_replace(
            '/\n{3,}/',
            "\n\n",
            $text
        ) ?? $text;

        return trim($text);
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
