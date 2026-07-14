<?php

namespace Tests\Unit;

use App\Support\RoadSheetDurationResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RoadSheetDurationResolverTest extends TestCase
{
    #[DataProvider('serviceNames')]
    public function test_it_extracts_the_service_duration(string $name, ?int $expected): void
    {
        $this->assertSame($expected, RoadSheetDurationResolver::fromServiceName($name));
    }

    public static function serviceNames(): array
    {
        return [
            ['Circuit 05 Jours', 5],
            ['Circuit 06 Jours', 6],
            ['Circuit 12 Jours', 12],
            ['Circuit de 5 jours', 5],
            ['Circuit 5 days', 5],
            ['Arrival transfer', null],
        ];
    }
}
