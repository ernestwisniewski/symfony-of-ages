<?php

namespace App\Domain\Game\Service;

use App\Domain\Player\Enum\TerrainType;
use App\Domain\Player\ValueObject\Position;

/**
 * MovementDomainService handles complex movement validation logic
 *
 * This is a domain service because movement validation involves:
 * - Multiple domain concepts (Position, TerrainType, distance)
 * - Complex business rules that don't belong to a single entity
 * - Pure domain logic without infrastructure dependencies
 */
class MovementDomainService
{
    /**
     * Validates if movement between two positions is allowed
     *
     * @param Position $from Starting position
     * @param Position $to Target position
     * @param array $terrainData Terrain data at target position
     * @return MovementValidationResult Validation result with details
     */
    public function validateMovement(Position $from, Position $to, array $terrainData): MovementValidationResult
    {
        // Check distance (can only move to adjacent hexes)
        $distance = $from->distanceTo($to);
        if ($distance > 1) {
            return MovementValidationResult::invalid('Can only move to adjacent hexes', MovementValidationResult::INVALID_DISTANCE);
        }

        // Check if terrain is passable
        $terrainType = TerrainType::from($terrainData['type']);
        $movementCost = $terrainType->getProperties()['movementCost'];

        if ($movementCost === 0) {
            return MovementValidationResult::invalid('Cannot move to impassable terrain', MovementValidationResult::IMPASSABLE_TERRAIN);
        }

        return MovementValidationResult::valid($movementCost);
    }

    /**
     * Calculates movement cost for specific terrain
     *
     * @param array $terrainData Terrain data
     * @return int Movement cost (0 = impassable)
     */
    public function calculateMovementCost(array $terrainData): int
    {
        $terrainType = TerrainType::from($terrainData['type']);
        return $terrainType->getProperties()['movementCost'];
    }

    /**
     * Checks if two positions are adjacent in hex grid
     *
     * @param Position $from Starting position
     * @param Position $to Target position
     * @return bool True if positions are adjacent
     */
    public function arePositionsAdjacent(Position $from, Position $to): bool
    {
        return $from->distanceTo($to) <= 1;
    }
}

/**
 * Result object for movement validation
 */
class MovementValidationResult
{
    public const VALID = 'valid';
    public const INVALID_DISTANCE = 'invalid_distance';
    public const IMPASSABLE_TERRAIN = 'impassable_terrain';

    private bool $isValid;
    private string $reason;
    private string $code;
    private int $movementCost;

    private function __construct(bool $isValid, string $reason, string $code, int $movementCost = 0)
    {
        $this->isValid = $isValid;
        $this->reason = $reason;
        $this->code = $code;
        $this->movementCost = $movementCost;
    }

    public static function valid(int $movementCost): self
    {
        return new self(true, 'Movement is valid', self::VALID, $movementCost);
    }

    public static function invalid(string $reason, string $code): self
    {
        return new self(false, $reason, $code);
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMovementCost(): int
    {
        return $this->movementCost;
    }
}
