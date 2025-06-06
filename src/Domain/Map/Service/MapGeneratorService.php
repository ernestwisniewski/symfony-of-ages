<?php

namespace App\Domain\Map\Service;

use App\Domain\Map\ValueObject\TerrainType;
use App\UI\Map\ViewModel\MapTileView;

class MapGeneratorService
{
    public function generateTiles(int $width, int $height): array
    {
        $tiles = [];
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $tiles[] = new MapTileView(
                    $x,
                    $y,
                    TerrainType::allValues()[random_int(0, TerrainType::count() - 1)],
                    false
                );
            }
        }

        return $tiles;
    }
}
