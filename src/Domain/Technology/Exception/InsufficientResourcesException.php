<?php

namespace App\Domain\Technology\Exception;

use App\Domain\Technology\ValueObject\TechnologyId;

final class InsufficientResourcesException extends TechnologyException
{
    public static function create(TechnologyId $technologyId, int $required, int $available): self
    {
        return new self("Technology {$technologyId} requires {$required} science points, but only {$available} are available.");
    }
}
