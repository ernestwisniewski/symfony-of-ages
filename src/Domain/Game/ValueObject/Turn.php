<?php

namespace App\Domain\Game\ValueObject;

use App\Domain\Game\Exception\GameException;
use App\Domain\Shared\ValueObject\ValidationConstants;

final readonly class Turn
{
    public function __construct(private int $number)
    {
        if ($this->number < ValidationConstants::MIN_TURN_NUMBER) {
            throw InvalidTurnException::negative($this->number);
        }
        if ($this->number > ValidationConstants::MAX_TURN_NUMBER) {
            throw InvalidTurnException::exceedsMax($this->number, ValidationConstants::MAX_TURN_NUMBER);
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

class InvalidTurnException extends GameException
{
    public static function negative(int $number): self
    {
        return new self("Turn number cannot be negative: $number.");
    }

    public static function exceedsMax(int $number, int $max): self
    {
        return new self("Turn number cannot exceed $max. Given: $number.");
    }
}
