<?php

namespace App\Domain\Map\Exception;

final class InvalidMapDimensionsException extends MapException
{
    public static function create(int $width, int $height): self
    {
        return new self("Invalid map dimensions: {$width}x{$height}. Both width and height must be greater than 0.");
    }

    public static function invalidWidth(int $width, int $min, int $max): self
    {
        return new self("Invalid map width: {$width}. Must be between {$min} and {$max}.");
    }

    public static function invalidHeight(int $height, int $min, int $max): self
    {
        return new self("Invalid map height: {$height}. Must be between {$min} and {$max}.");
    }
} 