<?php

namespace App\Domain\Game\ValueObject;
enum GameStatus: string
{
    case WAITING_FOR_PLAYERS = 'waiting';
    case IN_PROGRESS = 'in_progress';
    case FINISHED = 'finished';

    public function isFinished(): bool
    {
        return $this === self::FINISHED;
    }
}
