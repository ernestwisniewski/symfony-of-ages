<?php

namespace App\Domain\Player\Service;

/**
 * Result object for movement execution
 */
class MovementExecutionResult
{
    private function __construct(
        private readonly bool   $isSuccess,
        private readonly string $message,
        private readonly string $code,
        private readonly int    $movementCost = 0,
        private readonly int    $remainingMovementPoints = 0
    )
    {
    }

    public static function success(int $movementCost, int $remainingMovementPoints): self
    {
        return new self(
            true,
            'Movement executed successfully',
            'SUCCESS',
            $movementCost,
            $remainingMovementPoints
        );
    }

    public static function failed(string $message, string $code): self
    {
        return new self(false, $message, $code);
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMovementCost(): int
    {
        return $this->movementCost;
    }

    public function getRemainingMovementPoints(): int
    {
        return $this->remainingMovementPoints;
    }
}
