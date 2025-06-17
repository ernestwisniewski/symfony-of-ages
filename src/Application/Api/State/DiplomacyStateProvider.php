<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Infrastructure\Diplomacy\ReadModel\Doctrine\DiplomacyViewRepository;
use App\UI\Api\Resource\DiplomacyResource;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final readonly class DiplomacyStateProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus                $queryBus,
        private DiplomacyViewRepository $diplomacyViewRepository,
        private ObjectMapperInterface   $objectMapper,
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return match ($operation->getUriTemplate()) {
            '/diplomacy/{diplomacyId}' => $this->getDiplomacy($uriVariables['diplomacyId']),
            '/games/{gameId}/diplomacy' => $this->getDiplomacyByGame($uriVariables['gameId']),
            '/players/{playerId}/diplomacy' => $this->getDiplomacyByPlayer($uriVariables['playerId']),
            default => null,
        };
    }

    private function getDiplomacy(string $diplomacyId): ?DiplomacyResource
    {
        $diplomacyView = $this->diplomacyViewRepository->find($diplomacyId);
        if (!$diplomacyView) {
            return null;
        }
        return $this->objectMapper->map($diplomacyView, DiplomacyResource::class);
    }

    private function getDiplomacyByGame(string $gameId): array
    {
        $diplomacies = $this->diplomacyViewRepository->findByGameId($gameId);
        return array_map(
            fn($diplomacyView) => $this->objectMapper->map($diplomacyView, DiplomacyResource::class),
            $diplomacies
        );
    }

    private function getDiplomacyByPlayer(string $playerId): array
    {
        $diplomacies = $this->diplomacyViewRepository->findByPlayerId($playerId);
        return array_map(
            fn($diplomacyView) => $this->objectMapper->map($diplomacyView, DiplomacyResource::class),
            $diplomacies
        );
    }
}
