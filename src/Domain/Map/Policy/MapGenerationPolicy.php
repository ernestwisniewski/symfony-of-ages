<?php

namespace App\Domain\Map\Policy;

use App\Domain\Map\Exception\InvalidMapDimensionsException;

final readonly class MapGenerationPolicy
{
    private const int MIN_DIMENSION = 5;
    private const int MAX_DIMENSION = 50;

    public function canGenerateMap(int $width, int $height): bool
    {
        return $this->isDimensionValid($width) && $this->isDimensionValid($height);
    }

    public function validateMapGeneration(int $width, int $height): void
    {
        if (!$this->isDimensionValid($width) || !$this->isDimensionValid($height)) {
            throw InvalidMapDimensionsException::create($width, $height);
        }
    }

    public function getRecommendedDimensions(int $playerCount): array
    {
        return match (true) {
            $playerCount <= 2 => ['width' => 10, 'height' => 10],
            $playerCount <= 4 => ['width' => 15, 'height' => 15],
            default => ['width' => 20, 'height' => 20]
        };
    }

    private function isDimensionValid(int $dimension): bool
    {
        return $dimension >= self::MIN_DIMENSION && $dimension <= self::MAX_DIMENSION;
    }
} 