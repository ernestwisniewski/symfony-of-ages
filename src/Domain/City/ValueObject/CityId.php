<?php

namespace App\Domain\City\ValueObject;

use Symfony\Component\Uid\Uuid;

final class CityId
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
}
