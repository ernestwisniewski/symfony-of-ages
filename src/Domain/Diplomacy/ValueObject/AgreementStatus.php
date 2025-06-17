<?php

namespace App\Domain\Diplomacy\ValueObject;
enum AgreementStatus: string
{
    case PROPOSED = 'proposed';
    case ACCEPTED = 'accepted';
    case DECLINED = 'declined';
    case ENDED = 'ended';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::PROPOSED => 'Proposed',
            self::ACCEPTED => 'Active',
            self::DECLINED => 'Declined',
            self::ENDED => 'Ended',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACCEPTED;
    }

    public function isPending(): bool
    {
        return $this === self::PROPOSED;
    }

    public function isTerminated(): bool
    {
        return $this === self::DECLINED || $this === self::ENDED;
    }

    public function canBeAccepted(): bool
    {
        return $this === self::PROPOSED;
    }

    public function canBeDeclined(): bool
    {
        return $this === self::PROPOSED;
    }

    public function canBeEnded(): bool
    {
        return $this === self::ACCEPTED;
    }
}
