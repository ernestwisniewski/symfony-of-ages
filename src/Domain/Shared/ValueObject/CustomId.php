<?php

namespace App\Domain\Shared\ValueObject;

use App\Domain\Shared\Exception\DomainException;

final readonly class CustomId
{
    public function __construct(public int $id)
    {
        if ($this->id <= 0) {
            throw InvalidCustomIdException::notPositive($this->id);
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

class InvalidCustomIdException extends DomainException
{
    public static function notPositive(int $id): self
    {
        return new self("Custom ID must be a positive integer. Given: $id");
    }
}
