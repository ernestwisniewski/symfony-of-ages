<?php

namespace App\Application\Unit\Command;

use App\Application\Unit\DTO\TargetUnitDto;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;
use Ecotone\Modelling\Attribute\TargetIdentifier;

final readonly class AttackUnitCommand
{
    public function __construct(
        public UnitId        $unitId,
        public TargetUnitDto $targetUnit,
        public Timestamp     $attackedAt,
    )
    {
    }
}
