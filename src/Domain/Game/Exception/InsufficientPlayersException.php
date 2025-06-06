<?php

namespace App\Domain\Game\Exception;

final class InsufficientPlayersException extends GameException
{
    public static function create(int $required, int $actual): self
    {
        return new self("Minimum {$required} players required, but only {$actual} joined.");
    }
} 