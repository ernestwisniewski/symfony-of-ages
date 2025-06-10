<?php

namespace App\Domain\Shared\ValueObject;

use InvalidArgumentException;

final readonly class UserId
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

    public function equals(UserId $other): bool
    {
        return $this->id === $other->id;
    }
}
