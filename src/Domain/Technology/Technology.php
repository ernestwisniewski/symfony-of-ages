<?php

namespace App\Domain\Technology;

use App\Application\Technology\Command\CreateTechnologyCommand;
use App\Application\Technology\Command\DiscoverTechnologyCommand;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Technology\Event\TechnologyWasDiscovered;
use App\Domain\Technology\Exception\InsufficientResourcesException;
use App\Domain\Technology\Exception\InvalidTechnologyIdException;
use App\Domain\Technology\Exception\PrerequisiteNotMetException;
use App\Domain\Technology\Exception\TechnologyAlreadyDiscoveredException;
use App\Domain\Technology\Policy\TechnologyPolicy;
use App\Domain\Technology\ValueObject\TechnologyId;
use App\Domain\Technology\ValueObject\TechnologyType;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
class Technology
{
    use WithAggregateVersioning;

    #[Identifier]
    private PlayerId $playerId;
    private array $unlockedTechnologies = [];
    private int $sciencePoints = 0;

    public function __construct()
    {
    }

    #[CommandHandler]
    public static function create(CreateTechnologyCommand $command): array
    {
        return [
            new TechnologyWasDiscovered(
                technologyId: '',
                playerId: (string)$command->playerId,
                discoveredAt: $command->createdAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function discoverTechnology(
        DiscoverTechnologyCommand $command,
        TechnologyPolicy          $prerequisitesPolicy
    ): array
    {
        if ($this->hasTechnology($command->technologyId)) {
            throw TechnologyAlreadyDiscoveredException::create($command->technologyId);
        }

        $technology = $this->getTechnologyDefinition($command->technologyId);
        $missingPrerequisites = $prerequisitesPolicy->getMissingPrerequisites($technology, $this->unlockedTechnologies);
        if (!empty($missingPrerequisites)) {
            throw PrerequisiteNotMetException::create($command->technologyId, $missingPrerequisites);
        }

        $availableSciencePoints = 100;
        if ($availableSciencePoints < $technology['cost']) {
            throw InsufficientResourcesException::create($command->technologyId, $technology['cost'], $availableSciencePoints);
        }

        return [
            new TechnologyWasDiscovered(
                technologyId: (string)$command->technologyId,
                playerId: (string)$this->playerId,
                discoveredAt: $command->discoveredAt->format()
            )
        ];
    }

    #[EventSourcingHandler]
    public function whenTechnologyWasDiscovered(TechnologyWasDiscovered $event): void
    {
        if (empty($event->technologyId)) {
            $this->playerId = new PlayerId($event->playerId);
            $this->unlockedTechnologies = [];
            $this->sciencePoints = 0;
        } else {
            $this->unlockedTechnologies[] = new TechnologyId($event->technologyId);
        }
    }

    public function hasTechnology(TechnologyId $technologyId): bool
    {
        return array_any(
            $this->unlockedTechnologies,
            fn(TechnologyId $id) => $id->isEqual($technologyId)
        );
    }

    public function getUnlockedTechnologies(): array
    {
        return $this->unlockedTechnologies;
    }

    public function getUnlockedTechnologyIds(): array
    {
        return array_map(fn(TechnologyId $id) => (string)$id, $this->unlockedTechnologies);
    }

    public function getPlayerId(): PlayerId
    {
        return $this->playerId;
    }

    public function getSciencePoints(): int
    {
        return $this->sciencePoints;
    }

    private function getTechnologyDefinition(TechnologyId $technologyId): array
    {
        $technologyType = TechnologyType::tryFrom((string)$technologyId);
        if (!$technologyType) {
            throw InvalidTechnologyIdException::invalid((string)$technologyId);
        }

        return [
            'id' => (string)$technologyId,
            'name' => $technologyType->getDisplayName(),
            'description' => $technologyType->getDescription(),
            'cost' => $technologyType->getCost(),
            'prerequisites' => $technologyType->getPrerequisites(),
        ];
    }
}
