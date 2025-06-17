<?php

namespace App\Domain\Diplomacy\Exception;

use App\Domain\Player\ValueObject\PlayerId;

final class DiplomacyAlreadyExistsException extends DiplomacyException
{
    public static function create(PlayerId $player1, PlayerId $player2): self
    {
        return new self("Diplomatic agreement already exists between players {$player1} and {$player2}.");
    }
}
