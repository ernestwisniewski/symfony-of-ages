<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Game\Command\EndTurnCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\CommandBus;

final readonly class TurnStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBus $commandBus,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $gameId = $uriVariables['gameId'];
        $command = new EndTurnCommand(
            gameId: new GameId($gameId),
            playerId: new PlayerId($data->playerId),
            endedAt: Timestamp::now()
        );
        $this->commandBus->send($command);
    }
}
