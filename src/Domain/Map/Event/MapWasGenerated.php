<?php

namespace App\Domain\Map\Event;

use App\UI\Map\ViewModel\MapTileView;

final readonly class MapWasGenerated
{
    public function __construct(
        public string $gameId,
        public int    $width,
        public int    $height,
        public array  $tiles
    )
    {
    }
}
