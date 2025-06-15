<?php

namespace App\Application\City\Command;

use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;

final readonly class FoundCityCommand
{
    public function __construct(
        public CityId    $cityId,
        public PlayerId  $ownerId,
        public GameId    $gameId,
        public UnitId    $unitId,
        public CityName  $name,
        public Position  $position,
        public Timestamp $foundedAt,
        public array     $existingCityPositions = []
    )
    {
    }
}
