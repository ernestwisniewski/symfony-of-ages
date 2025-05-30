<?php

namespace App\Domain\Player\ValueObject;

use App\Domain\Player\Exception\InvalidPlayerDataException;

/**
 * MovementPoints value object for player movement management
 *
 * Immutable value object that encapsulates movement points logic,
 * validation, and business rules for player movement capabilities
 * within a turn. Uses readonly properties to ensure true immutability.
 */
class MovementPoints
{
    public readonly int $current;
    public readonly int $maximum;

    public function __construct(int $current, int $maximum)
    {
        if ($current < 0) {
            throw InvalidPlayerDataException::negativeMovementPoints('Current');
        }

        if ($maximum < 0) {
            throw InvalidPlayerDataException::negativeMovementPoints('Maximum');
        }

        if ($current > $maximum) {
            throw InvalidPlayerDataException::movementPointsExceedMaximum();
        }

        $this->current = $current;
        $this->maximum = $maximum;
    }

    public function canSpend(int $cost): bool
    {
        return $this->current >= $cost && $cost >= 0;
    }

    public function spend(int $cost): self
    {
        if (!$this->canSpend($cost)) {
            throw InvalidPlayerDataException::cannotSpendMovementPoints($cost, $this->current);
        }

        return new self($this->current - $cost, $this->maximum);
    }

    public function restore(): self
    {
        return new self($this->maximum, $this->maximum);
    }

    public function hasPointsRemaining(): bool
    {
        return $this->current > 0;
    }

    public function isEmpty(): bool
    {
        return $this->current === 0;
    }

    public function toArray(): array
    {
        return [
            'current' => $this->current,
            'maximum' => $this->maximum
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['current'], $data['maximum']);
    }

    public static function createFull(int $maximum): self
    {
        return new self($maximum, $maximum);
    }
}
