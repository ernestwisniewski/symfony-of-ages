<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Api\Resource\GameResource;
use App\Application\Game\Command\CreateGameCommand;
use App\Application\Game\Command\JoinGameCommand;
use App\Application\Game\Command\StartGameCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

final readonly class GameStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBus $commandBus,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof GameResource) {
            throw new BadRequestHttpException('Expected GameResource');
        }

        match ($operation->getUriTemplate()) {
            '/games' => $this->createGame($data),
            '/games/{gameId}/start' => $this->startGame($uriVariables['gameId']),
            '/games/{gameId}/join' => $this->joinGame($uriVariables['gameId'], $data),
            default => throw new BadRequestHttpException('Unsupported operation'),
        };
    }

    private function createGame(GameResource $data): void
    {
        $this->commandBus->send(new CreateGameCommand(
            gameId: new GameId(Uuid::v4()->toRfc4122()),
            playerId: new PlayerId(Uuid::v4()->toRfc4122()),
            name: new GameName($data->name),
            createdAt: Timestamp::now()
        ));
    }

    private function startGame(string $gameId): void
    {
        $this->commandBus->send(new StartGameCommand(
            gameId: new GameId($gameId),
            startedAt: Timestamp::now()
        ));
    }

    private function joinGame(string $gameId, GameResource $data): void
    {
        $this->commandBus->send(new JoinGameCommand(
            gameId: new GameId($gameId),
            playerId: new PlayerId($data->playerId),
            joinedAt: Timestamp::now()
        ));
    }
}
