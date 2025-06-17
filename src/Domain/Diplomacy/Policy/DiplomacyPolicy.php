<?php

namespace App\Domain\Diplomacy\Policy;

use App\Domain\Diplomacy\ValueObject\AgreementType;
use App\Domain\Diplomacy\ValueObject\PlayerRelation;
use App\Domain\Player\ValueObject\PlayerId;

final readonly class DiplomacyPolicy
{
    public function canPropose(PlayerId $initiator, PlayerId $target, AgreementType $type): bool
    {
        return !$initiator->isEqual($target);
    }

    public function canAccept(PlayerId $player, PlayerRelation $relation): bool
    {
        return $relation->isTarget($player);
    }

    public function canDecline(PlayerId $player, PlayerRelation $relation): bool
    {
        return $relation->isTarget($player);
    }

    public function canEnd(PlayerId $player, PlayerRelation $relation): bool
    {
        return $relation->involves($player);
    }
}
