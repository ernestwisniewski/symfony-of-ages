<?php

namespace App\Domain\City\Event;
final readonly class CityWasFounded
{
    public function __construct(
        public string $cityId,
        public string $ownerId,
        public string $unitId,
        public string $name,
        public int    $x,
        public int    $y,
        public string $foundedAt
    )
    {
    }
}
