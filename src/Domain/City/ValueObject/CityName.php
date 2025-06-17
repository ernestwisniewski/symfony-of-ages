<?php

namespace App\Domain\City\ValueObject;

use App\Domain\Shared\ValueObject\ValidationConstants;
use DomainException;

final readonly class CityName
{
    public function __construct(public string $name)
    {
        if (trim($name) === '' || mb_strlen($name) > ValidationConstants::MAX_CITY_NAME_LENGTH_DOMAIN) {
            throw new DomainException('Invalid city name.');
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
