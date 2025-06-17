<?php

namespace App\Domain\Diplomacy\ValueObject;

use Symfony\Component\Uid\Uuid;

final class DiplomacyId
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

    public function isEqual(DiplomacyId $other): bool
    {
        return $this->id === $other->id;
    }
}
