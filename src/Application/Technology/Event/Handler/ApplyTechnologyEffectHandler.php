<?php

namespace App\Application\Technology\Event\Handler;

use App\Domain\Technology\Effect\BonusEffect;
use App\Domain\Technology\Effect\UnlockUnitEffect;
use App\Domain\Technology\Event\TechnologyWasDiscovered;
use App\Domain\Technology\Repository\TechnologyRepository;
use App\Domain\Technology\ValueObject\TechnologyId;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;

final readonly class ApplyTechnologyEffectHandler
{
    public function __construct(
        private TechnologyRepository $technologyRepository,
        private CommandBus           $commandBus
    )
    {
    }

    #[EventHandler]
    public function handle(TechnologyWasDiscovered $event): void
    {
        if (empty($event->technologyId)) {
            return;
        }
        $technology = $this->technologyRepository->findBy(new TechnologyId($event->technologyId));
        if (!$technology) {
            return;
        }
        foreach ($technology->getEffects() as $effect) {
            $this->applyEffect($effect, $event);
        }
    }

    private function applyEffect($effect, TechnologyWasDiscovered $event): void
    {
        $context = [
            'playerId' => $event->playerId,
            'gameId' => $event->gameId,
            'technologyId' => $event->technologyId
        ];
        $effect->apply($context);
        if ($effect instanceof UnlockUnitEffect) {
            $this->handleUnlockUnitEffect($effect, $event);
        } elseif ($effect instanceof BonusEffect) {
            $this->handleBonusEffect($effect, $event);
        }
    }

    private function handleUnlockUnitEffect($effect, TechnologyWasDiscovered $event): void
    {
    }

    private function handleBonusEffect($effect, TechnologyWasDiscovered $event): void
    {
    }
}
