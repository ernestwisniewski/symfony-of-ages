<?php

namespace App\Domain\Player\Service;

use App\Domain\Map\Enum\TerrainType;
use App\Domain\Player\ValueObject\Position;
use App\Domain\Shared\Service\HexGridService;

/**
 * MovementDomainService handles player movement validation logic
 *
 * This is a domain service because movement validation involves:
 * - Player-specific movement constraints (movement points, position)
 * - Map terrain considerations (passability, movement cost)
 * - Domain business rules that don't belong to a single entity
 * - Pure domain logic without infrastructure dependencies
 */
class MovementDomainService
{
    public function __construct(
        private readonly HexGridService $hexGridService
    ) {
    }

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
        // Check if terrain is passable
        $terrainType = TerrainType::from($terrainData['type']);
        if (!$terrainType->isPassable()) {
            return MovementValidationResult::invalid(
                "Cannot move to impassable terrain: {$terrainType->getName()}",
                'IMPASSABLE_TERRAIN'
            );
        }

        // Check if positions are adjacent
        if (!$this->arePositionsAdjacent($from, $to)) {
            return MovementValidationResult::invalid(
                'Target position is not adjacent to current position',
                'NOT_ADJACENT'
            );
        }

        $movementCost = $this->calculateMovementCost($terrainData);
        return MovementValidationResult::valid($movementCost);
    }

    /**
     * Calculates movement cost for terrain
     */
    public function calculateMovementCost(array $terrainData): int
    {
        $terrainType = TerrainType::from($terrainData['type']);
        return $terrainType->getMovementCost();
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
        return $this->hexGridService->arePositionsAdjacent($from, $to);
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