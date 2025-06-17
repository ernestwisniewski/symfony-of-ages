<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Technology\Query\GetAllTechnologiesQuery;
use App\Application\Technology\Query\GetTechnologyDetailsQuery;
use App\Application\Technology\Query\GetTechnologyTreeQuery;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Technology\ValueObject\TechnologyId;
use App\UI\Api\Resource\TechnologyResource;
use App\UI\Technology\ViewModel\TechnologyView;
use Ecotone\Modelling\QueryBus;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class TechnologyStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus              $queryBus,
        private ObjectMapperInterface $objectMapper,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $uriTemplate = $operation->getUriTemplate();
        return match (true) {
            str_contains($uriTemplate, '/technologies/{technologyId}') => $this->getTechnology($uriVariables['technologyId']),
            str_contains($uriTemplate, '/technologies') && !str_contains($uriTemplate, '/{technologyId}') => $this->getAllTechnologies(),
            str_contains($uriTemplate, '/players/{playerId}/technologies') => $this->getPlayerTechnologies($uriVariables['playerId']),
            default => null,
        };
    }

    private function getTechnology(string $technologyId): ?TechnologyResource
    {
        try {
            $technologyView = $this->queryBus->send(new GetTechnologyDetailsQuery(new TechnologyId($technologyId)));
        } catch (RuntimeException $e) {
            throw new NotFoundHttpException("Technology with ID $technologyId not found");
        }
        if (!$technologyView) {
            throw new NotFoundHttpException("Technology with ID $technologyId not found");
        }
        return $this->objectMapper->map($technologyView, TechnologyResource::class);
    }

    private function getAllTechnologies(): array
    {
        $technologyViews = $this->queryBus->send(new GetAllTechnologiesQuery());
        return array_map(
            fn(TechnologyView $technologyView) => $this->objectMapper->map($technologyView, TechnologyResource::class),
            $technologyViews
        );
    }

    private function getPlayerTechnologies(string $playerId): array
    {
        try {
            $technologyTreeView = $this->queryBus->send(new GetTechnologyTreeQuery(new PlayerId($playerId)));
        } catch (RuntimeException $e) {
            throw new NotFoundHttpException("Technology tree for player $playerId not found");
        }
        return [
            'playerId' => $technologyTreeView->playerId,
            'unlockedTechnologies' => $technologyTreeView->unlockedTechnologies,
            'availableTechnologies' => $technologyTreeView->availableTechnologies,
            'sciencePoints' => $technologyTreeView->sciencePoints
        ];
    }
}
