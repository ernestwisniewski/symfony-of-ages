<?php

namespace App\Domain\City\ValueObject;

use App\Domain\City\Exception\CityException;
use App\Domain\Shared\ValueObject\ValidationConstants;

final readonly class CityName
{
    public function __construct(public string $name)
    {
        if (trim($name) === '' || mb_strlen($name) > ValidationConstants::MAX_CITY_NAME_LENGTH_DOMAIN) {
            throw InvalidCityNameException::invalid($name);
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }
}

class InvalidCityNameException extends CityException
{
    public static function invalid(string $name): self
    {
        return new self("Invalid city name: '$name'");
    }
}
