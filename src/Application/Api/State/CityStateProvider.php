<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\City\Query\GetCityViewQuery;
use App\Application\Exception\ResourceNotFoundException;
use App\Domain\City\ValueObject\CityId;
use App\Infrastructure\City\ReadModel\Doctrine\CityViewRepository;
use App\UI\Api\Resource\CityResource;
use Ecotone\Modelling\QueryBus;
use RuntimeException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class CityStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus              $queryBus,
        private CityViewRepository    $cityViewRepository,
        private ObjectMapperInterface $objectMapper,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (isset($uriVariables['cityId'])) {
            return $this->provideCity($uriVariables['cityId']);
        }
        return $this->provideCitiesForGame($uriVariables['gameId']);
    }

    private function provideCity(string $cityId): CityResource
    {
        try {
            $cityView = $this->queryBus->send(new GetCityViewQuery(new CityId($cityId)));
        } catch (RuntimeException $e) {
            throw ResourceNotFoundException::cityNotFound($cityId);
        }
        return $this->objectMapper->map($cityView, CityResource::class);
    }

    private function provideCitiesForGame(string $gameId): array
    {
        $cityViewEntities = $this->cityViewRepository->findByGameId($gameId);
        return array_map(
            fn($cityView) => $this->objectMapper->map($cityView, CityResource::class),
            $cityViewEntities
        );
    }
}
