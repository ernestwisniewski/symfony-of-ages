<?php

namespace App\Infrastructure\Map\Query;

final readonly class FindMapByGame
{
    public function __construct(
        public string $gameId
    )
    {
    }
}
