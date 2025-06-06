<?php

namespace App\Domain\Game\Exception;

final class GameFullException extends GameException
{
    public static function create(int $maxPlayers): self
    {
        return new self("Maximum {$maxPlayers} players allowed, game is full.");
    }
} 