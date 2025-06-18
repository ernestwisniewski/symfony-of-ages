<?php

namespace App\Domain\Shared\ValueObject;

use App\Domain\Shared\Exception\DomainException;

class InvalidPositionException extends DomainException
{
    public static function negative(int $x, int $y): self
    {
        return new self("Invalid position: ($x, $y)");
    }
}

final readonly class Position
{
    public function __construct(public int $x, public int $y)
    {
        if ($x < 0 || $y < 0) {
            throw InvalidPositionException::negative($x, $y);
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
