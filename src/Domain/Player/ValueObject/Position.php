<?php

namespace App\Domain\Player\ValueObject;

/**
 * Position value object representing coordinates on the hexagonal grid
 *
 * Immutable value object that encapsulates row and column coordinates
 * for positioning entities on the hexagonal map. Provides utility
 * methods for coordinate manipulation and validation.
 */
class Position
{
    private int $row;
    private int $col;

    public function __construct(int $row, int $col)
    {
        $this->row = $row;
        $this->col = $col;
    }

    public function getRow(): int
    {
        return $this->row;
    }

    public function getCol(): int
    {
        return $this->col;
    }

    /**
     * Checks if this position is equal to another position
     */
    public function equals(Position $other): bool
    {
        return $this->row === $other->row && $this->col === $other->col;
    }

    /**
     * Validates if position is within map bounds
     */
    public function isValidForMap(int $maxRows, int $maxCols): bool
    {
        return $this->row >= 0 && $this->row < $maxRows &&
            $this->col >= 0 && $this->col < $maxCols;
    }

    /**
     * Calculates distance to another position (for hexagonal grid)
     */
    public function distanceTo(Position $other): int
    {
        // Hexagonal distance calculation
        $dx = $this->col - $other->col;
        $dy = $this->row - $other->row;

        // Adjust for hexagonal coordinate system
        if (($this->row % 2) !== ($other->row % 2)) {
            if ($this->row % 2 === 0) {
                $dx += 0.5;
            } else {
                $dx -= 0.5;
            }
        }

        return max(abs($dx), abs($dy), abs($dx + $dy));
    }

    /**
     * Gets array representation for client consumption
     */
    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'col' => $this->col
        ];
    }

    /**
     * Creates position from array data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['row'], $data['col']);
    }

    public function __toString(): string
    {
        return "({$this->row}, {$this->col})";
    }
}
