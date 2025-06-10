<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\City\Command\FoundCityCommand;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use App\UI\Api\Resource\CityResource;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
            default => throw new BadRequestHttpException('Unsupported operation'),
        };
    }

    private function foundCity(string $gameId, $data): void
    {
        $this->commandBus->send(new FoundCityCommand(
            cityId: new CityId(Uuid::v4()->toRfc4122()),
            ownerId: new PlayerId(Uuid::v4()->toRfc4122()),
            gameId: new GameId($gameId),
            name: new CityName($data->name),
            position: new Position($data->x, $data->y),
            foundedAt: Timestamp::now(),
            existingCityPositions: [] // TODO: Get from repository if needed for validation
        ));
    }
}
