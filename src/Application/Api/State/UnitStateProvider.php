<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Exception\ResourceNotFoundException;
use App\Application\Unit\Query\GetUnitViewQuery;
use App\Domain\Unit\ValueObject\UnitId;
use App\Infrastructure\Unit\ReadModel\Doctrine\UnitViewRepository;
use App\UI\Api\Resource\UnitResource;
use Ecotone\Modelling\QueryBus;
use RuntimeException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class UnitStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus              $queryBus,
        private UnitViewRepository    $unitViewRepository,
        private ObjectMapperInterface $objectMapper,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $uriTemplate = $operation->getUriTemplate();
        return match (true) {
            str_contains($uriTemplate, '/units/{unitId}') => $this->getUnit($uriVariables['unitId']),
            str_contains($uriTemplate, '/games/{gameId}/units') => $this->getUnitsByGame($uriVariables['gameId']),
            default => null,
        };
    }

    private function getUnit(string $unitId): ?UnitResource
    {
        try {
            $unitView = $this->queryBus->send(new GetUnitViewQuery(new UnitId($unitId)));
        } catch (RuntimeException $e) {
            throw ResourceNotFoundException::unitNotFound($unitId);
        }
        return $this->objectMapper->map($unitView, UnitResource::class);
    }

    private function getUnitsByGame(string $gameId): array
    {
        $unitViewEntities = $this->unitViewRepository->findByGameId($gameId);
        return array_map(
            fn($entity) => $this->objectMapper->map($entity, UnitResource::class),
            $unitViewEntities
        );
    }
}
