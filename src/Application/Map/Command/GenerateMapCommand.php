<?php

namespace App\Application\Map\Command;

use App\Domain\Game\ValueObject\GameId;

class GenerateMapCommand
{

    public function __construct(
        public GameId $gameId,
        public array  $tiles,
        public int    $width,
        public int    $height,
    )
    {
    }
}
