<?php

namespace App\Infrastructure\Technology\ReadModel;

use App\Application\Technology\Query\GetAllTechnologiesQuery;
use App\Application\Technology\Query\GetAvailableTechnologiesQuery;
use App\Application\Technology\Query\GetTechnologyDetailsQuery;
use App\Application\Technology\Query\GetTechnologyQuery;
use App\Domain\Technology\Event\TechnologyWasDiscovered;
use App\Domain\Technology\Technology;
use App\Domain\Technology\ValueObject\TechnologyType;
use App\Infrastructure\Technology\ReadModel\Doctrine\PlayerTechnologyEntity;
use App\Infrastructure\Technology\ReadModel\Doctrine\PlayerTechnologyRepository;
use App\UI\Technology\ViewModel\PlayerTechnologyView;
use App\UI\Technology\ViewModel\TechnologyView;
use Doctrine\ORM\EntityManagerInterface;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[Projection("technology_projection", Technology::class)]
final readonly class TechnologyProjection
{
    public function __construct(
        private EntityManagerInterface     $entityManager,
        private PlayerTechnologyRepository $playerTechnologyRepository,
        private ObjectMapperInterface      $objectMapper
    )
    {
    }

    #[QueryHandler]
    public function getTechnologyTree(GetTechnologyQuery $query): TechnologyView
    {
        $playerTechnologies = $this->getPlayerTechnologies((string)$query->playerId);
        $allTechnologies = $this->getAllTechnologyTypes();
        $unlockedTechnologyIds = array_map(
            fn(PlayerTechnologyView $pt) => $pt->technologyId,
            $playerTechnologies
        );
        $availableTechnologies = $this->getAvailableTechnologiesForPlayer(
            $allTechnologies,
            $unlockedTechnologyIds,
            100
        );
        $technologyTreeView = new TechnologyView();
        $technologyTreeView->playerId = (string)$query->playerId;
        $technologyTreeView->unlockedTechnologies = $unlockedTechnologyIds;
        $technologyTreeView->availableTechnologies = array_map(
            fn($tech) => $tech['id'],
            $availableTechnologies
        );
        $technologyTreeView->sciencePoints = 100;
        return $technologyTreeView;
    }

    #[QueryHandler]
    public function getAvailableTechnologies(GetAvailableTechnologiesQuery $query): array
    {
        $playerTechnologies = $this->getPlayerTechnologies((string)$query->playerId);
        $allTechnologies = $this->getAllTechnologyTypes();
        $unlockedTechnologyIds = array_map(
            fn(PlayerTechnologyView $pt) => $pt->technologyId,
            $playerTechnologies
        );
        $availableTechnologies = $this->getAvailableTechnologiesForPlayer(
            $allTechnologies,
            $unlockedTechnologyIds,
            100
        );
        return array_map(
            fn($technology) => $this->createTechnologyView($technology, $unlockedTechnologyIds),
            $availableTechnologies
        );
    }

    #[QueryHandler]
    public function getAllTechnologies(GetAllTechnologiesQuery $query): array
    {
        $allTechnologies = $this->getAllTechnologyTypes();
        return array_map(
            fn($technology) => $this->createTechnologyView($technology, []),
            $allTechnologies
        );
    }

    #[QueryHandler]
    public function getTechnologyDetails(GetTechnologyDetailsQuery $query): ?TechnologyView
    {
        $technologyType = TechnologyType::tryFrom((string)$query->technologyId);
        if (!$technologyType) {
            return null;
        }
        $technology = $this->createTechnologyFromType($technologyType);
        return $this->createTechnologyView($technology, []);
    }

    #[EventHandler]
    public function applyTechnologyWasDiscovered(TechnologyWasDiscovered $event): void
    {
        if (empty($event->technologyId)) {
            return;
        }
        $playerTechnology = new PlayerTechnologyEntity(
            $event->playerId,
            $event->technologyId,
            $event->gameId,
            $event->discoveredAt
        );
        $this->entityManager->persist($playerTechnology);
        $this->entityManager->flush();
    }

    public function getPlayerTechnologies(string $playerId): array
    {
        $entities = $this->playerTechnologyRepository->findByPlayerId($playerId);
        return array_map(
            fn(PlayerTechnologyEntity $entity) => $this->objectMapper->map($entity, PlayerTechnologyView::class),
            $entities
        );
    }

    public function getGameTechnologies(string $gameId): array
    {
        $entities = $this->playerTechnologyRepository->findByGameId($gameId);
        return array_map(
            fn(PlayerTechnologyEntity $entity) => $this->objectMapper->map($entity, PlayerTechnologyView::class),
            $entities
        );
    }

    private function getAllTechnologyTypes(): array
    {
        $technologies = [];
        foreach (TechnologyType::cases() as $type) {
            $technologies[] = $this->createTechnologyFromType($type);
        }
        return $technologies;
    }

    private function createTechnologyFromType(TechnologyType $type): array
    {
        return [
            'id' => $type->value,
            'name' => $type->getDisplayName(),
            'description' => $type->getDescription(),
            'cost' => $type->getCost(),
            'prerequisites' => $type->getPrerequisites(),
        ];
    }

    private function getAvailableTechnologiesForPlayer(array $allTechnologies, array $unlockedTechnologyIds, int $availableSciencePoints): array
    {
        return array_filter(
            $allTechnologies,
            function ($technology) use ($unlockedTechnologyIds, $availableSciencePoints) {
                if (in_array($technology['id'], $unlockedTechnologyIds, true)) {
                    return false;
                }
                if ($technology['cost'] > $availableSciencePoints) {
                    return false;
                }
                return true;
            }
        );
    }

    private function createTechnologyView(array $technology, array $unlockedTechnologyIds): TechnologyView
    {
        $technologyView = new TechnologyView();
        $technologyView->id = $technology['id'];
        $technologyView->name = $technology['name'];
        $technologyView->description = $technology['description'];
        $technologyView->cost = $technology['cost'];
        $technologyView->prerequisites = $technology['prerequisites'];
        $technologyView->effects = [];
        $technologyView->isUnlocked = in_array($technology['id'], $unlockedTechnologyIds, true);
        $technologyView->isAvailable = !$technologyView->isUnlocked && $technology['cost'] <= 100;
        return $technologyView;
    }
}
