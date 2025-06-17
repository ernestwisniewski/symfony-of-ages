<?php

namespace App\Application\Unit\Command;

use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;

final readonly class DestroyUnitCommand
{
    public function __construct(
        public UnitId    $unitId,
        public Timestamp $destroyedAt
    )
    {
    }
}
