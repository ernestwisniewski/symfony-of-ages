<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\City\Command\FoundCityCommand;
use App\Application\Exception\InvalidOperationException;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\City\ValueObject\UnitId;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\Uid\Uuid;

final readonly class CityStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBus $commandBus,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        match ($operation->getUriTemplate()) {
            '/games/{gameId}/cities' => $this->foundCity($uriVariables['gameId'], $data),
            default => throw InvalidOperationException::unsupportedOperation($operation->getUriTemplate()),
        };
    }

    private function foundCity(string $gameId, $data): void
    {
        $this->commandBus->send(new FoundCityCommand(
            cityId: new CityId(Uuid::v4()->toRfc4122()),
            ownerId: new PlayerId($data->playerId ?? Uuid::v4()->toRfc4122()),
            gameId: new GameId($gameId),
            unitId: new UnitId($data->unitId),
            name: new CityName($data->name),
            position: new Position($data->x, $data->y),
            foundedAt: Timestamp::now(),
            existingCityPositions: []
        ));
    }
}
