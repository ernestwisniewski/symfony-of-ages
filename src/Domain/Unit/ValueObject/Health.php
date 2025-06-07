<?php

namespace App\Domain\Unit\ValueObject;

use DomainException;

final readonly class Health
{
    public function __construct(
        public int $current,
        public int $maximum
    )
    {
        if ($current < 0) {
            throw new DomainException('Health cannot be negative');
        }

        if ($maximum <= 0) {
            throw new DomainException('Maximum health must be positive');
        }

        if ($current > $maximum) {
            throw new DomainException('Current health cannot exceed maximum');
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
        if ($damage < 0) {
            throw new DomainException('Damage cannot be negative');
        }

        $newCurrent = max(0, $this->current - $damage);

        return new self($newCurrent, $this->maximum);
    }

    public function heal(int $healing): self
    {
        if ($healing < 0) {
            throw new DomainException('Healing cannot be negative');
        }

        $newCurrent = min($this->maximum, $this->current + $healing);

        return new self($newCurrent, $this->maximum);
    }
}
