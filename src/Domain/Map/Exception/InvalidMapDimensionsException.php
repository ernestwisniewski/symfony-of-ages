<?php

namespace App\Domain\Map\Exception;

final class InvalidMapDimensionsException extends MapException
{
    public static function create(int $width, int $height): self
    {
        return new self("Invalid map dimensions: {$width}x{$height}. Both width and height must be greater than 0.");
    }
} 