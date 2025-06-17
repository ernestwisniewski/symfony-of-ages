<?php

namespace App\Domain\Map\Policy;

use App\Domain\Game\ValueObject\GameId;
use App\Domain\Map\Exception\InvalidMapDimensionsException;
use App\Domain\Shared\ValueObject\ValidationConstants;

final readonly class MapGenerationPolicy
{
    public function validateDimensions(int $width, int $height): void
    {
        if ($width < ValidationConstants::MIN_MAP_SIZE || $width > ValidationConstants::MAX_MAP_SIZE) {
            throw InvalidMapDimensionsException::invalidWidth($width, ValidationConstants::MIN_MAP_SIZE, ValidationConstants::MAX_MAP_SIZE);
        }

        if ($height < ValidationConstants::MIN_MAP_SIZE || $height > ValidationConstants::MAX_MAP_SIZE) {
            throw InvalidMapDimensionsException::invalidHeight($height, ValidationConstants::MIN_MAP_SIZE, ValidationConstants::MAX_MAP_SIZE);
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
} 