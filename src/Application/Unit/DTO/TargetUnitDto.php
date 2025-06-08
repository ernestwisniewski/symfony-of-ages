<?php

namespace App\Application\Unit\DTO;

use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Unit\ValueObject\Health;
use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Unit\ValueObject\UnitType;
use App\UI\Unit\ViewModel\UnitView;

final readonly class TargetUnitDto
{
    public function __construct(
        public UnitId   $unitId,
        public PlayerId $ownerId,
        public Position $position,
        public UnitType $type,
        public Health   $health
    )
    {
    }

    public static function fromUnitView(UnitView $unitView): self
    {
        return new self(
            unitId: new UnitId($unitView->id),
            ownerId: new PlayerId($unitView->ownerId),
            position: new Position($unitView->position['x'], $unitView->position['y']),
            type: UnitType::from($unitView->type),
            health: new Health($unitView->currentHealth, $unitView->maxHealth)
        );
    }
}
