<?php

namespace App\Domain\Player\ValueObject;

use App\Domain\Shared\Exception\DomainException;

class InvalidPlayerNameException extends DomainException
{
    public static function invalid(string $name): self
    {
        return new self("Invalid player name: '$name'");
    }
}

final class PlayerName
{
    public function __construct(public string $name)
    {
        if (trim($name) === '' || mb_strlen($name) > 50) {
            throw InvalidPlayerNameException::invalid($name);
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
