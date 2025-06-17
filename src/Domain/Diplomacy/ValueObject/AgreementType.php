<?php

namespace App\Domain\Diplomacy\ValueObject;
enum AgreementType: string
{
    case NON_AGGRESSION_PACT = 'non_aggression_pact';
    case ALLIANCE = 'alliance';
    case WAR = 'war';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::NON_AGGRESSION_PACT => 'Non-Aggression Pact',
            self::ALLIANCE => 'Alliance',
            self::WAR => 'War',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::NON_AGGRESSION_PACT => 'Agreement to not attack each other for a specified period',
            self::ALLIANCE => 'Military and economic cooperation between players',
            self::WAR => 'Active state of conflict between players',
        };
    }

    public function isHostile(): bool
    {
        return $this === self::WAR;
    }

    public function isPeaceful(): bool
    {
        return $this === self::NON_AGGRESSION_PACT || $this === self::ALLIANCE;
    }

    public function allowsTrade(): bool
    {
        return $this === self::ALLIANCE;
    }

    public function allowsMilitaryCooperation(): bool
    {
        return $this === self::ALLIANCE;
    }
}
