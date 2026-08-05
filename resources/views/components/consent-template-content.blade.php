@props([
    'html' => null,
    'text' => null,
    'organizationId',
])

@php
    $renderedConsentContent =
        app(\App\Services\ConsentTemplateContentService::class)
            ->render(
                is_string($html) ? $html : null,
                is_string($text) ? $text : null,
                (int) $organizationId
            );
@endphp

<div {{ $attributes->merge([
    'class' => 'consent-rich-content',
]) }}>
    {!! $renderedConsentContent !!}
</div>
