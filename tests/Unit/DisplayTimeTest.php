<?php

namespace Tests\Unit;

use App\Support\DisplayTime;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DisplayTimeTest extends TestCase
{
    public function test_it_converts_utc_time_to_nairobi_time(): void
    {
        config([
            'app.timezone' => 'UTC',
            'app.display_timezone' => 'Africa/Nairobi',
        ]);

        $utc = CarbonImmutable::parse(
            '2026-08-03 20:28:09',
            'UTC'
        );

        $this->assertSame(
            '2026-08-03 23:28:09 EAT',
            DisplayTime::format(
                $utc,
                'Y-m-d H:i:s T'
            )
        );
    }

    public function test_it_preserves_a_fallback_for_missing_time(): void
    {
        $this->assertSame(
            'Not recorded',
            DisplayTime::format(
                null,
                'Y-m-d H:i:s',
                'Not recorded'
            )
        );
    }
}
