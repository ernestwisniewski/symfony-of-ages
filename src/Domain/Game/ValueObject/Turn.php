<?php

namespace App\Domain\Game\ValueObject;

use InvalidArgumentException;

final readonly class Turn
{
    public function __construct(private int $number)
    {
        if ($this->number < 0) {
            throw new InvalidArgumentException("Turn number cannot be negative.");
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
