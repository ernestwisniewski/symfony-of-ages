<?php

namespace App\Application\Technology\Service;

use App\Application\Technology\Command\DiscoverTechnologyCommand;
use App\Application\Technology\Query\GetAllTechnologiesQuery;
use App\Application\Technology\Query\GetAvailableTechnologiesQuery;
use App\Application\Technology\Query\GetTechnologyDetailsQuery;
use App\Application\Technology\Query\GetTechnologyTreeQuery;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Technology\Repository\TechnologyRepository;
use App\Domain\Technology\Repository\TechnologyTreeRepository;
use App\Domain\Technology\Service\TechnologyManagementService;
use App\Domain\Technology\ValueObject\TechnologyId;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;

final readonly class TechnologyApplicationService
{
    public function __construct(
        private TechnologyManagementService $technologyManagementService,
        private TechnologyRepository        $technologyRepository,
        private TechnologyTreeRepository    $technologyTreeRepository,
        private CommandBus                  $commandBus,
        private QueryBus                    $queryBus
    )
    {
    }

    public function discoverTechnology(PlayerId $playerId, TechnologyId $technologyId, GameId $gameId): array
    {
        $technology = $this->technologyRepository->findBy($technologyId);
        if (!$technology) {
            return ['success' => false, 'reason' => 'Technology not found'];
        }
        $technologyTree = $this->technologyTreeRepository->findBy($playerId);
        if (!$technologyTree) {
            return ['success' => false, 'reason' => 'Technology tree not found'];
        }
        $availableSciencePoints = $this->getAvailableSciencePoints($playerId);
        $unlockedTechnologies = $technologyTree->getUnlockedTechnologies();
        if (!$this->technologyManagementService->canDiscoverTechnology(
            $technology,
            $unlockedTechnologies,
            $availableSciencePoints
        )) {
            return ['success' => false, 'reason' => 'Cannot discover technology'];
        }
        $this->commandBus->send(new DiscoverTechnologyCommand(
            $playerId,
            $technologyId,
            $gameId,
            Timestamp::now()
        ));
        return ['success' => true, 'message' => 'Technology discovered successfully'];
    }

    public function getTechnologyTree(PlayerId $playerId): array
    {
        $technologyTreeView = $this->queryBus->send(new GetTechnologyTreeQuery($playerId));
        return [
            'unlockedTechnologies' => $technologyTreeView->unlockedTechnologies,
            'availableTechnologies' => $technologyTreeView->availableTechnologies,
            'sciencePoints' => $technologyTreeView->sciencePoints
        ];
    }

    public function getAvailableTechnologies(PlayerId $playerId): array
    {
        return $this->queryBus->send(new GetAvailableTechnologiesQuery($playerId));
    }

    public function getTechnologyDetails(TechnologyId $technologyId): ?array
    {
        $technologyView = $this->queryBus->send(new GetTechnologyDetailsQuery($technologyId));
        if (!$technologyView) {
            return null;
        }
        return [
            'id' => $technologyView->id,
            'name' => $technologyView->name,
            'description' => $technologyView->description,
            'cost' => $technologyView->cost,
            'prerequisites' => $technologyView->prerequisites,
            'effects' => $technologyView->effects
        ];
    }

    public function getAllTechnologies(): array
    {
        return $this->queryBus->send(new GetAllTechnologiesQuery());
    }

    private function getAvailableSciencePoints(PlayerId $playerId): int
    {
        return 100;
    }
}
