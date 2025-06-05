<?php

namespace App\Application\City\Query;

use App\Domain\City\ValueObject\CityId;

final readonly class GetCityViewQuery
{
    public function __construct(public CityId $cityId)
    {
    }
}
