<?php

namespace App\Domain\Diplomacy\Exception;

use App\Domain\Diplomacy\ValueObject\DiplomacyId;

final class DiplomacyNotFoundException extends DiplomacyException
{
    public static function create(DiplomacyId $diplomacyId): self
    {
        return new self("Diplomatic agreement with ID {$diplomacyId} not found.");
    }
}
