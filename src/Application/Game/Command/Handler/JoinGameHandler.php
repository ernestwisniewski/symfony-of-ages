<?php

namespace App\Application\Game\Command\Handler;

use App\Application\Game\Command\CreateGameCommand;
use App\Application\Game\Command\JoinGameCommand;
use App\Application\Player\Command\CreatePlayerCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\CommandBus;

readonly class JoinGameHandler
{
    public function __construct(
        private CommandBus $bus,
    )
    {
    }

    #[CommandHandler]
    public function handleJoin(JoinGameCommand $command): void
    {
        $this->send($command->playerId, $command->gameId);
    }

    #[CommandHandler]
    public function handleCreate(CreateGameCommand $command): void
    {
        $this->send($command->playerId, $command->gameId);
    }

    private function send(PlayerId $playerId, GameId $gameId): void
    {
        $this->bus->send(new CreatePlayerCommand(
            $playerId,
            $gameId,
        ));
    }
}
