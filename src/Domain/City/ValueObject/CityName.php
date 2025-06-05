<?php

namespace App\Domain\City\ValueObject;

final readonly class CityName
{
    const int MAX_LENGTH = 255;

    public function __construct(public string $name)
    {
        if (trim($name) === '' || mb_strlen($name) > self::MAX_LENGTH) {
            throw new \DomainException('Invalid city name.');
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
