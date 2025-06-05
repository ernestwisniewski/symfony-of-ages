<?php

namespace App\Domain\Map\ValueObject;

class Position
{
    public function __construct(public int $x, public int $y)
    {
        if ($x < 0 || $y < 0) {
            throw new \DomainException('Invalid position.');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self($data['x'], $data['y']);
    }

    public function toArray(): array
    {
        return ['x' => $this->x, 'y' => $this->y];
    }
}
