<?php

namespace App\Domain\Game\ValueObject;

use App\Domain\Shared\ValueObject\ValidationConstants;
use InvalidArgumentException;

final readonly class Turn
{
    public function __construct(private int $number)
    {
        if ($this->number < ValidationConstants::MIN_TURN_NUMBER) {
            throw new InvalidArgumentException("Turn number cannot be negative.");
        }

        if ($this->number > ValidationConstants::MAX_TURN_NUMBER) {
            throw new InvalidArgumentException("Turn number cannot exceed " . ValidationConstants::MAX_TURN_NUMBER . ".");
        }
    }

    public function current(): int
    {
        return $this->number;
    }

    public function next(): self
    {
        return new self($this->number + 1);
    }

    public function __toString(): string
    {
        return (string)$this->number;
    }
}
