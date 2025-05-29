<?php

namespace App\Domain\Game\ValueObject;

use InvalidArgumentException;

/**
 * MovementPoints value object for player movement management
 *
 * Encapsulates movement points logic, validation, and business rules
 * for player movement capabilities within a turn.
 */
class MovementPoints
{
    private int $current;
    private int $maximum;

    public function __construct(int $current, int $maximum)
    {
        if ($maximum < 0) {
            throw new InvalidArgumentException('Maximum movement points cannot be negative');
        }

        if ($current < 0) {
            throw new InvalidArgumentException('Current movement points cannot be negative');
        }

        if ($current > $maximum) {
            throw new InvalidArgumentException('Current movement points cannot exceed maximum');
        }

        $this->current = $current;
        $this->maximum = $maximum;
    }

    public function getCurrent(): int
    {
        return $this->current;
    }

    public function getMaximum(): int
    {
        return $this->maximum;
    }

    public function canSpend(int $cost): bool
    {
        return $this->current >= $cost && $cost >= 0;
    }

    public function spend(int $cost): self
    {
        if (!$this->canSpend($cost)) {
            throw new InvalidArgumentException("Cannot spend {$cost} movement points. Available: {$this->current}");
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
