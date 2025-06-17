<?php

namespace App\Domain\Unit\ValueObject;

use App\Domain\Shared\ValueObject\ValidationConstants;
use DomainException;

final readonly class Health
{
    public function __construct(
        public int $current,
        public int $maximum
    )
    {
        if ($current < ValidationConstants::MIN_HEALTH_VALUE) {
            throw new DomainException('Health cannot be negative');
        }
        if ($maximum <= ValidationConstants::MIN_HEALTH_VALUE) {
            throw new DomainException('Maximum health must be positive');
        }
        if ($current > $maximum) {
            throw new DomainException('Current health cannot exceed maximum');
        }
        if ($maximum > ValidationConstants::MAX_HEALTH_VALUE) {
            throw new DomainException('Maximum health cannot exceed ' . ValidationConstants::MAX_HEALTH_VALUE);
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
            throw new DomainException('Damage cannot be negative');
        }
        $newCurrent = max(ValidationConstants::MIN_HEALTH_VALUE, $this->current - $damage);
        return new self($newCurrent, $this->maximum);
    }

    public function heal(int $healing): self
    {
        if ($healing < ValidationConstants::MIN_HEALTH_VALUE) {
            throw new DomainException('Healing cannot be negative');
        }
        $newCurrent = min($this->maximum, $this->current + $healing);
        return new self($newCurrent, $this->maximum);
    }
}
