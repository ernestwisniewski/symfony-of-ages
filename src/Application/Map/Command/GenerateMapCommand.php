<?php

namespace App\Application\Map\Command;

class GenerateMapCommand
{

    public function __construct(
        public string $gameId,
        public int    $width,
        public int    $height,
    )
    {
    }
}
