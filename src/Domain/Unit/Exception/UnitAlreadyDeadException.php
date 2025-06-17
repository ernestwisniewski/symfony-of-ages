<?php

namespace App\Domain\Unit\Exception;

use App\Domain\Unit\ValueObject\UnitId;

final class UnitAlreadyDeadException extends UnitException
{
    public static function create(UnitId $unitId): self
    {
        return new self("Unit {$unitId} is already dead and cannot perform actions.");
    }
}
