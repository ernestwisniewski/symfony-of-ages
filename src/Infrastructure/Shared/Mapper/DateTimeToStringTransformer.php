<?php

namespace App\Infrastructure\Shared\Mapper;

use App\Domain\Shared\ValueObject\Timestamp;
use DateTimeImmutable;

final class DateTimeToStringTransformer
{
    public static function format(?DateTimeImmutable $value, object $source): ?string
    {
        return $value ? new Timestamp($value)->format() : null;
    }
}
