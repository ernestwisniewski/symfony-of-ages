<?php

namespace App\Domain\Game\ValueObject;

final class GameName
{
    const int MAX_LENGTH = 120;

    public function __construct(public string $name)
    {
        if(self::MAX_LENGTH < mb_strlen($this->name)) {
            throw new \DomainException('Game name is too long.');
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
