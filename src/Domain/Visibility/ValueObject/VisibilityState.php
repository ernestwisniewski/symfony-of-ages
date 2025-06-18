<?php

namespace App\Domain\Visibility\ValueObject;

enum VisibilityState: string
{
    case ACTIVE = 'active';
    case DISCOVERED = 'discovered';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::DISCOVERED => 'Discovered',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isDiscovered(): bool
    {
        return $this === self::DISCOVERED;
    }
} 