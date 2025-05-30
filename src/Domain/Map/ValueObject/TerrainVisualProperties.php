<?php

namespace App\Domain\Map\ValueObject;

/**
 * TerrainVisualProperties represents visual characteristics of terrain
 *
 * Value Object containing display-related properties like name and color.
 * Used by rendering systems and UI components.
 */
readonly class TerrainVisualProperties
{
    public function __construct(
        private string $name,
        private int $color
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColor(): int
    {
        return $this->color;
    }

    public function getHexColor(): string
    {
        return sprintf('#%06X', $this->color);
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