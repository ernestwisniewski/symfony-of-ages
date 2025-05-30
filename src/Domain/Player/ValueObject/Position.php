<?php

namespace App\Domain\Player\ValueObject;

use App\Domain\Player\Exception\InvalidPlayerDataException;

/**
 * Position value object representing coordinates on the hexagonal grid
 *
 * Immutable value object that encapsulates row and column coordinates
 * for positioning entities on the hexagonal map. Provides utility
 * methods for coordinate manipulation and validation.
 * Uses readonly properties to ensure true immutability.
 */
class Position
{
    public readonly int $row;
    public readonly int $col;

    public function __construct(int $row, int $col)
    {
        if ($row < 0) {
            throw InvalidPlayerDataException::negativeRow();
        }

        if ($col < 0) {
            throw InvalidPlayerDataException::negativeColumn();
        }

        $this->row = $row;
        $this->col = $col;
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
        // Proper hexagonal distance calculation using axial coordinates
        $q1 = $this->col - ($this->row + ($this->row & 1)) / 2;
        $r1 = $this->row;

        $q2 = $other->col - ($other->row + ($other->row & 1)) / 2;
        $r2 = $other->row;

        return intval((abs($q1 - $q2) + abs($q1 + $r1 - $q2 - $r2) + abs($r1 - $r2)) / 2);
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
        if (!isset($data['row'])) {
            throw InvalidPlayerDataException::missingRowData();
        }

        if (!isset($data['col'])) {
            throw InvalidPlayerDataException::missingColumnData();
        }

        return new self($data['row'], $data['col']);
    }

    public function __toString(): string
    {
        return "({$this->row}, {$this->col})";
    }
}
