<?php

namespace App\Domain\Game\Event;

final readonly class MapWasGenerated
{
    public function __construct(
        public string $gameId,
        public string $tiles,
        public int    $width,
        public int    $height
    )
    {
    }
}
