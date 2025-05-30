<?php

namespace App\Domain\Player\Service;

/**
 * Result object for movement validation
 */
class MovementValidationResult
{
    public const VALID = 'valid';
    public const INVALID_DISTANCE = 'invalid_distance';
    public const IMPASSABLE_TERRAIN = 'impassable_terrain';

    private function __construct(
        private readonly bool   $isValid,
        private readonly string $reason,
        private readonly string $code,
        private readonly int    $movementCost = 0
    )
    {
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
