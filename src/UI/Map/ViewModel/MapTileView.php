<?php

namespace App\UI\Map\ViewModel;
class MapTileView
{
    public function __construct(
        public int    $x,
        public int    $y,
        public string $terrain,
        public bool   $isOccupied
    )
    {
    }
}
