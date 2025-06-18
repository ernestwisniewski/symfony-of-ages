<?php

namespace App\Domain\Diplomacy\Exception;

class DiplomacyOperationException extends DiplomacyException
{
    public static function cannotProposeToSelf(): self
    {
        return new self('Cannot propose diplomacy to yourself');
    }

    public static function onlyTargetCanAccept(): self
    {
        return new self('Only the target player can accept this diplomacy proposal');
    }

    public static function canOnlyAcceptProposed(): self
    {
        return new self('Can only accept proposed diplomacy agreements');
    }

    public static function onlyTargetCanDecline(): self
    {
        return new self('Only the target player can decline this diplomacy proposal');
    }

    public static function canOnlyDeclineProposed(): self
    {
        return new self('Can only decline proposed diplomacy agreements');
    }

    public static function onlyInvolvedCanEnd(): self
    {
        return new self('Only involved players can end this diplomacy agreement');
    }

    public static function canOnlyEndAccepted(): self
    {
        return new self('Can only end accepted diplomacy agreements');
    }
}
