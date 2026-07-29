<?php

$positiveDays = static function (
    string $environmentKey,
    string $default
): array {
    return collect(
        explode(
            ',',
            (string) env(
                $environmentKey,
                $default
            )
        )
    )
        ->map(
            fn ($days): int =>
                (int) trim((string) $days)
        )
        ->filter(
            fn (int $days): bool =>
                $days > 0
        )
        ->unique()
        ->sortDesc()
        ->values()
        ->all();
};

return [
    'before_due_days' => $positiveDays(
        'SUBSCRIPTION_INVOICE_REMINDER_BEFORE_DUE_DAYS',
        '3,1'
    ),

    'overdue_days' => $positiveDays(
        'SUBSCRIPTION_INVOICE_REMINDER_OVERDUE_DAYS',
        '7,1'
    ),

    'automatic_retry_minutes' => max(
        1,
        (int) env(
            'SUBSCRIPTION_INVOICE_REMINDER_RETRY_MINUTES',
            60
        )
    ),

    'chunk_size' => max(
        1,
        (int) env(
            'SUBSCRIPTION_INVOICE_REMINDER_CHUNK_SIZE',
            100
        )
    ),
];
