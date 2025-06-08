<?php

namespace App\Application\Unit\Command;

use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;

final readonly class MoveUnitCommand
{
    public function __construct(
        public UnitId    $unitId,
        public Position  $toPosition,
        public array     $existingUnits,
        public Timestamp $movedAt
    )
    {
    }
}
