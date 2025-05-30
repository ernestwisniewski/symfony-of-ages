<?php

namespace App\Domain\Map\ValueObject;

use App\Domain\Map\Exception\InvalidTerrainDataException;

/**
 * TerrainVisualProperties encapsulates visual-related terrain characteristics
 *
 * Immutable value object that represents the visual appearance and display
 * properties of different terrain types for rendering and user interface.
 * Uses readonly properties to ensure true immutability.
 */
class TerrainVisualProperties
{
    public readonly string $name;
    public readonly int $color;

    public function __construct(string $name, int $color)
    {
        if (empty(trim($name))) {
            throw InvalidTerrainDataException::emptySymbol();
        }

        if (strlen($name) > 20) {
            throw InvalidTerrainDataException::symbolTooLong(20);
        }

        if ($color < 0 || $color > 0xFFFFFF) {
            throw InvalidTerrainDataException::invalidColorValue($color);
        }

        $this->name = $name;
        $this->color = $color;
    }

    /**
     * Gets hex representation of color with # prefix
     */
    public function getHexColor(): string
    {
        return '#' . strtoupper(str_pad(dechex($this->color), 6, '0', STR_PAD_LEFT));
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'color' => $this->color,
            'hexColor' => $this->getHexColor()
        ];
    }
}
