<?php

namespace App\Domain\Shared\ValueObject;

use DomainException;

final readonly class Position
{
    public function __construct(public int $x, public int $y)
    {
        if ($x < 0 || $y < 0) {
            throw new DomainException("Invalid position");
        }
    }

    public function toArray(): array
    {
        return ['x' => $this->x, 'y' => $this->y];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['x'], $data['y']);
    }

    public function isEqual(self $other): bool
    {
        return $this->x === $other->x && $this->y === $other->y;
    }
}

