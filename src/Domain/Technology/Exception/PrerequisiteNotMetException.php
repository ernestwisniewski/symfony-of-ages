<?php

namespace App\Domain\Technology\Exception;

use App\Domain\Technology\ValueObject\TechnologyId;

final class PrerequisiteNotMetException extends TechnologyException
{
    public static function create(TechnologyId $technologyId, array $missingPrerequisites): self
    {
        $missingList = implode(', ', array_map(fn($id) => (string)$id, $missingPrerequisites));
        return new self("Technology {$technologyId} requires prerequisites that are not met: {$missingList}");
    }
}
