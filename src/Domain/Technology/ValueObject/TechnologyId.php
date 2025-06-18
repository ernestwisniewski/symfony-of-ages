<?php

namespace App\Domain\Technology\ValueObject;

use App\Domain\Technology\ValueObject\TechnologyType;

final class TechnologyId
{
    public function __construct(public string $id)
    {
        if (!TechnologyType::tryFrom($this->id)) {
            throw new \InvalidArgumentException('Invalid technology ID: ' . $this->id);
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
