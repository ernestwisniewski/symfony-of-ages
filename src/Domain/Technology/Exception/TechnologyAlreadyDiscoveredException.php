<?php

namespace App\Domain\Technology\Exception;

use App\Domain\Technology\ValueObject\TechnologyId;

final class TechnologyAlreadyDiscoveredException extends TechnologyException
{
    public static function create(TechnologyId $technologyId): self
    {
        return new self("Technology {$technologyId} has already been discovered.");
    }
}
