<?php

namespace App\Domain\Technology\ValueObject;

use Symfony\Component\Uid\Uuid;

final class TechnologyId
{
    private Uuid $uuid;

    public function __construct(public string $id)
    {
        $this->uuid = Uuid::fromString($this->id);
    }

    public function __toString(): string
    {
        return $this->uuid->toRfc4122();
    }

    public function isEqual(TechnologyId $other): bool
    {
        return $this->id === $other->id;
    }
}
