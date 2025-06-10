<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Game\Command\EndTurnCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\UI\Api\Resource\TurnResource;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class TurnStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBus        $commandBus,
        private TurnStateProvider $stateProvider,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TurnResource|null
    {
        if (!$data instanceof TurnResource) {
            throw new BadRequestHttpException('Invalid data type');
        }

        $gameId = $uriVariables['gameId'] ?? null;
        if (!$gameId) {
            throw new BadRequestHttpException('Game ID is required');
        }


        $endedAt = Timestamp::now();

        $command = new EndTurnCommand(
            gameId: new GameId($gameId),
            playerId: new PlayerId($data->playerId),
            endedAt: $endedAt
        );

        $this->commandBus->send($command);

        // CQRS: Commands should not return data, only execute actions
        return null;
    }
}
