<?php

namespace App\Domain\Game\Exception;

use App\Domain\Shared\Exception\DomainException;

abstract class GameException extends DomainException
{
}

class GameStateCorruptedException extends GameException
{
    public static function activePlayerNotFound($activePlayer): self
    {
        return new self(sprintf('Active player %s not found in player list. Game state is corrupted.', (string)$activePlayer));
    }
}
