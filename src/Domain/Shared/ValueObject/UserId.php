<?php

namespace App\Domain\Shared\ValueObject;

use App\Domain\Shared\Exception\DomainException;

final readonly class UserId
{
    public function __construct(public int $id)
    {
        if ($this->id <= 0) {
            throw InvalidUserIdException::notPositive($this->id);
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

class InvalidUserIdException extends DomainException
{
    public static function notPositive(int $id): self
    {
        return new self("User ID must be a positive integer. Given: $id");
    }
}
