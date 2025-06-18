<?php

namespace App\Domain\Technology\ValueObject;

use App\Domain\Technology\Exception\TechnologyException;

final class TechnologyId
{
    public function __construct(public string $id)
    {
        if (!TechnologyType::tryFrom($this->id)) {
            throw InvalidTechnologyIdException::invalid($this->id);
        }
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function isEqual(TechnologyId $other): bool
    {
        return $this->id === $other->id;
    }
}

class InvalidTechnologyIdException extends TechnologyException
{
    public static function invalid(string $id): self
    {
        return new self('Invalid technology ID: ' . $id);
    }
}
