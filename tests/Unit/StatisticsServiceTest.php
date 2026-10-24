<?php

namespace Tests\Unit;

use App\Services\StatisticsService;
use PHPUnit\Framework\TestCase;

class StatisticsServiceTest extends TestCase
{
    public function test_zone_is_read_from_the_address(): void
    {
        $this->assertSame('Houston (77001)', StatisticsService::zoneFromAddress('123 Main St, Houston, TX 77001'));
        $this->assertSame('Katy (77494)', StatisticsService::zoneFromAddress('45 Oak Ln, Katy TX 77494'));
        $this->assertSame('Conroe', StatisticsService::zoneFromAddress('Lot 4 Phase 2, Conroe'));
        $this->assertSame('77301', StatisticsService::zoneFromAddress('9 Elm St 77301'));
        $this->assertSame('Unknown area', StatisticsService::zoneFromAddress('9 Elm St'));
        $this->assertSame('Unknown area', StatisticsService::zoneFromAddress(null));
    }

    public function test_pie_keeps_the_biggest_slices_and_folds_the_rest(): void
    {
        $rows = array_map(fn ($i) => ['name' => "B{$i}", 'earned' => $i * 10], range(1, 10));

        $slices = StatisticsService::pie($rows, 'earned');

        $this->assertCount(StatisticsService::PIE_SLICES + 1, $slices);
        $this->assertSame('B10', $slices[0]['label']);
        $this->assertSame('Other (3)', end($slices)['label']);
        $this->assertSame(60.0, end($slices)['value']);
    }

    public function test_pie_skips_empty_rows(): void
    {
        $this->assertSame([], StatisticsService::pie([['name' => 'A', 'earned' => 0]], 'earned'));
    }
}
