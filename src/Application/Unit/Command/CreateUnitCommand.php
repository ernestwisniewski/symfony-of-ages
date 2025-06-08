<?php

namespace App\Application\Unit\Command;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Unit\ValueObject\UnitType;

final readonly class CreateUnitCommand
{
    public function __construct(
        public UnitId    $unitId,
        public PlayerId  $ownerId,
        public GameId    $gameId,
        public UnitType  $type,
        public Position  $position,
        public Timestamp $createdAt
    )
    {
    }
}
