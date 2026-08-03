<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Throwable;

final class DisplayTime
{
    public static function timezone(): string
    {
        $timezone = trim(
            (string) config(
                'app.display_timezone',
                'Africa/Nairobi'
            )
        );

        try {
            new DateTimeZone($timezone);
            return $timezone;
        } catch (Throwable) {
            return 'UTC';
        }
    }

    public static function format(
        DateTimeInterface|string|null $value,
        string $format,
        string $fallback = '—'
    ): string {
        if ($value === null || $value === '') {
            return $fallback;
        }

        try {
            $date =
                $value instanceof DateTimeInterface
                    ? CarbonImmutable::instance($value)
                    : CarbonImmutable::parse(
                        $value,
                        (string) config('app.timezone', 'UTC')
                    );

            return $date
                ->setTimezone(self::timezone())
                ->format($format);
        } catch (Throwable) {
            return $fallback;
        }
    }
}
