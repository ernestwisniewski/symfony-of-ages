<?php

namespace App\Application\City\Command;

use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\City\ValueObject\Position;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;

final readonly class FoundCityCommand
{
    public function __construct(
        public CityId      $cityId,
        public PlayerId    $ownerId,
        public GameId      $gameId,
        public CityName    $name,
        public Position    $position,
        public TerrainType $terrain,
        public Timestamp   $foundedAt,
        public array       $existingCityPositions = []
    )
    {
    }
}
