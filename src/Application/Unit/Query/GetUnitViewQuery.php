<?php

namespace App\Application\Unit\Query;

use App\Domain\Unit\ValueObject\UnitId;

final readonly class GetUnitViewQuery
{
    public function __construct(public UnitId $unitId)
    {
    }
}
