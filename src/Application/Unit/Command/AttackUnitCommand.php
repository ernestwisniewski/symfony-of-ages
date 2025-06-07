<?php

namespace App\Application\Unit\Command;

use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;
use App\UI\Unit\ViewModel\UnitView;
use Ecotone\Modelling\Attribute\TargetIdentifier;

final readonly class AttackUnitCommand
{
    public function __construct(
        public UnitId    $attackerUnitId,
        public UnitView  $targetUnitView,
        public Timestamp $attackedAt,
    )
    {
    }
}
