<?php

namespace App\Application\Technology\Service;

use App\Application\Technology\Command\DiscoverTechnologyCommand;
use App\Application\Technology\Query\GetAllTechnologiesQuery;
use App\Application\Technology\Query\GetAvailableTechnologiesQuery;
use App\Application\Technology\Query\GetTechnologyDetailsQuery;
use App\Application\Technology\Query\GetTechnologyQuery;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Technology\ValueObject\TechnologyId;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;

final readonly class TechnologyApplicationService
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus   $queryBus
    )
    {
    }

    public function discoverTechnology(PlayerId $playerId, string $technologyType): array
    {
        $technologyId = new TechnologyId($technologyType);
        $this->commandBus->send(new DiscoverTechnologyCommand(
            $playerId,
            $technologyId,
            Timestamp::now()
        ));
        return ['success' => true, 'message' => 'Technology discovered successfully'];
    }

    public function getTechnologyTree(PlayerId $playerId): array
    {
        $technologyTreeView = $this->queryBus->send(new GetTechnologyQuery($playerId));
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

    public function getTechnologyDetails(string $technologyType): ?array
    {
        $technologyId = new TechnologyId($technologyType);
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
}
