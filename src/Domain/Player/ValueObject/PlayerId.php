<?php

namespace App\Domain\Player\ValueObject;

use Symfony\Component\Uid\Uuid;

class PlayerId
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

    public function isEqual(PlayerId $playerId): bool
    {
        return $this->id === $playerId->__toString();
    }
}
