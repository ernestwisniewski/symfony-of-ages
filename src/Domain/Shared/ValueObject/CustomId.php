<?php

namespace App\Domain\Shared\ValueObject;

use InvalidArgumentException;

final readonly class CustomId
{
    public function __construct(public int $id)
    {
        if ($this->id <= 0) {
            throw new InvalidArgumentException('User ID must be a positive integer');
        }
    }

    public function __toString(): string
    {
        return (string)$this->id;
    }

    public function equals(CustomId $other): bool
    {
        return $this->id === $other->id;
    }
}
