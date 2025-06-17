<?php

namespace App\Domain\Technology;

use App\Application\Technology\Command\CreateTechnologyTreeCommand;
use App\Application\Technology\Command\DiscoverTechnologyCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Technology\Event\TechnologyWasDiscovered;
use App\Domain\Technology\Exception\InsufficientResourcesException;
use App\Domain\Technology\Exception\PrerequisiteNotMetException;
use App\Domain\Technology\Exception\TechnologyAlreadyDiscoveredException;
use App\Domain\Technology\Policy\TechnologyPrerequisitesPolicy;
use App\Domain\Technology\ValueObject\TechnologyId;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
class TechnologyTree
{
    use WithAggregateVersioning;

    #[Identifier]
    private PlayerId $playerId;
    private GameId $gameId;
    private array $unlockedTechnologies = [];
    private int $sciencePoints = 0;

    public function __construct()
    {
    }

    #[CommandHandler]
    public static function create(CreateTechnologyTreeCommand $command): array
    {
        return [
            new TechnologyWasDiscovered(
                technologyId: '',
                playerId: (string)$command->playerId,
                gameId: (string)$command->gameId,
                discoveredAt: $command->createdAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function discoverTechnology(
        DiscoverTechnologyCommand     $command,
        Technology                    $technology,
        TechnologyPrerequisitesPolicy $prerequisitesPolicy
    ): array
    {
        if ($this->hasTechnology($command->technologyId)) {
            throw TechnologyAlreadyDiscoveredException::create($command->technologyId);
        }
        $missingPrerequisites = $prerequisitesPolicy->getMissingPrerequisites($technology, $this->unlockedTechnologies);
        if (!empty($missingPrerequisites)) {
            throw PrerequisiteNotMetException::create($command->technologyId, $missingPrerequisites);
        }
        $availableSciencePoints = 100;
        if ($availableSciencePoints < $technology->cost) {
            throw InsufficientResourcesException::create($command->technologyId, $technology->cost, $availableSciencePoints);
        }
        return [
            new TechnologyWasDiscovered(
                technologyId: (string)$command->technologyId,
                playerId: (string)$this->playerId,
                gameId: (string)$this->gameId,
                discoveredAt: $command->discoveredAt->format()
            )
        ];
    }

    #[EventSourcingHandler]
    public function whenTechnologyWasDiscovered(TechnologyWasDiscovered $event): void
    {
        if (empty($event->technologyId)) {
            $this->playerId = new PlayerId($event->playerId);
            $this->gameId = new GameId($event->gameId);
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

    public function getGameId(): GameId
    {
        return $this->gameId;
    }

    public function getSciencePoints(): int
    {
        return $this->sciencePoints;
    }
}
