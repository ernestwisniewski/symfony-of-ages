<?php

namespace App\Application\Unit\Command;

use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Shared\ValueObject\Timestamp;

final readonly class DestroyUnitCommand
{
    public function __construct(
        public UnitId    $unitId,
        public Timestamp $destroyedAt
    )
    {
    }
} 