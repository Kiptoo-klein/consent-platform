@props([
    'title',
    'value' => null,
])

<div
    {{ $attributes->merge([
        'class' =>
            'rounded-xl border border-gray-200 bg-gray-50 p-4'
    ]) }}
>
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
        {{ $title }}
    </p>

    <div class="mt-2 text-sm font-medium text-gray-900">
        @if (! is_null($value))
            {{ filled($value) ? $value : 'Not provided' }}
        @else
            {{ $slot }}
        @endif
    </div>
</div>
