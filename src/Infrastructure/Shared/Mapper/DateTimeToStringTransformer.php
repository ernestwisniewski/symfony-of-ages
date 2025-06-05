<?php

namespace App\Infrastructure\Shared\Mapper;

use App\Domain\Shared\ValueObject\Timestamp;

final class DateTimeToStringTransformer
{
    public static function format(\DateTimeImmutable $value, object $source): string
    {
        return new Timestamp($value)->format();
    }
}
