<?php

namespace App\Domain\Unit\ValueObject;

use App\Domain\Shared\ValueObject\ValidationConstants;
use App\Domain\Unit\Exception\UnitException;

final readonly class Health
{
    public function __construct(
        public int $current,
        public int $maximum
    )
    {
        if ($current < ValidationConstants::MIN_HEALTH_VALUE) {
            throw InvalidHealthException::negative($current);
        }
        if ($maximum <= ValidationConstants::MIN_HEALTH_VALUE) {
            throw InvalidHealthException::maxNotPositive($maximum);
        }
        if ($current > $maximum) {
            throw InvalidHealthException::currentExceedsMax($current, $maximum);
        }
        if ($maximum > ValidationConstants::MAX_HEALTH_VALUE) {
            throw InvalidHealthException::maxExceedsLimit($maximum, ValidationConstants::MAX_HEALTH_VALUE);
        }
    }

    public static function full(int $maximum): self
    {
        return new self($maximum, $maximum);
    }

    public function isDead(): bool
    {
        return $this->current === 0;
    }

    public function isFullHealth(): bool
    {
        return $this->current === $this->maximum;
    }

    public function getHealthPercentage(): float
    {
        return ($this->current / $this->maximum) * 100;
    }

    public function takeDamage(int $damage): self
    {
        if ($damage < ValidationConstants::MIN_HEALTH_VALUE) {
            throw InvalidHealthException::damageNegative($damage);
        }
        $newCurrent = max(ValidationConstants::MIN_HEALTH_VALUE, $this->current - $damage);
        return new self($newCurrent, $this->maximum);
    }

    public function heal(int $healing): self
    {
        if ($healing < ValidationConstants::MIN_HEALTH_VALUE) {
            throw InvalidHealthException::healingNegative($healing);
        }
        $newCurrent = min($this->maximum, $this->current + $healing);
        return new self($newCurrent, $this->maximum);
    }
}

class InvalidHealthException extends UnitException
{
    public static function negative(int $value): self
    {
        return new self("Health cannot be negative: $value");
    }

    public static function maxNotPositive(int $value): self
    {
        return new self("Maximum health must be positive: $value");
    }

    public static function currentExceedsMax(int $current, int $max): self
    {
        return new self("Current health ($current) cannot exceed maximum ($max)");
    }

    public static function maxExceedsLimit(int $max, int $limit): self
    {
        return new self("Maximum health cannot exceed $limit. Given: $max");
    }

    public static function damageNegative(int $damage): self
    {
        return new self("Damage cannot be negative: $damage");
    }

    public static function healingNegative(int $healing): self
    {
        return new self("Healing cannot be negative: $healing");
    }
}
