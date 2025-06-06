<?php

namespace App\Application\City\Command;

use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\City\ValueObject\Position;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Player\ValueObject\PlayerId;

final readonly class FoundCityCommand
{
    public function __construct(
        public CityId     $cityId,
        public PlayerId   $ownerId,
        public CityName   $name,
        public Position   $position,
        public TerrainType $terrain,
        public array      $existingCityPositions = []
    )
    {
    }
}
