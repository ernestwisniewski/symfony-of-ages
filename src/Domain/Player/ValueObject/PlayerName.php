<?php

namespace App\Domain\Player\ValueObject;

final class PlayerName
{
    public function __construct(public string $name)
    {
        if (trim($name) === '' || mb_strlen($name) > 50) {
            throw new \DomainException("Invalid player name.");
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
