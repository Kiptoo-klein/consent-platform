@props(['status'])

@php
    $rawStatus = $status instanceof \BackedEnum
        ? $status->value
        : (string) $status;

    $normalizedStatus = strtolower(
        str_replace([' ', '-'], '_', trim($rawStatus))
    );

    $classes = match ($normalizedStatus) {
        'completed',
        'complete',
        'signed',
        'approved' =>
            'border-green-700 bg-green-600 text-white',

        'in_progress',
        'processing',
        'active' =>
            'border-blue-300 bg-blue-100 text-blue-800',

        'pending',
        'sent',
        'awaiting_signature' =>
            'border-amber-300 bg-amber-100 text-amber-800',

        'cancelled',
        'canceled',
        'abandoned',
        'failed',
        'rejected' =>
            'border-red-300 bg-red-100 text-red-800',

        'expired' =>
            'border-gray-400 bg-gray-200 text-gray-700',

        default =>
            'border-gray-300 bg-gray-100 text-gray-700',
    };

    $label = str($normalizedStatus)
        ->replace('_', ' ')
        ->title();
@endphp

<span
    {{ $attributes->merge([
        'class' =>
            'inline-flex items-center rounded-full border px-3 py-1 ' .
            'text-xs font-semibold leading-none ' .
            $classes
    ]) }}
>
    {{ $label }}
</span>
