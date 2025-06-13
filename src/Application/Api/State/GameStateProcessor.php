<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Game\Command\CreateGameCommand;
use App\Application\Game\Command\JoinGameCommand;
use App\Application\Game\Command\StartGameCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Shared\ValueObject\UserId;
use App\Infrastructure\Generic\Account\Doctrine\User;
use App\UI\Api\Resource\GameResource;
use Ecotone\Modelling\CommandBus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;
use Ecotone\Modelling\AggregateNotFoundException;

final readonly class GameStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private Security   $security,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $userId = new UserId($this->security->getUser()->getId());

        match ($operation->getUriTemplate()) {
            '/games' => $this->createGame($data, $userId),
            '/games/{gameId}/start' => $this->startGame($uriVariables['gameId']),
            '/games/{gameId}/join' => $this->joinGame($uriVariables['gameId'], $data, $userId),
            default => throw new BadRequestHttpException('Unsupported operation'),
        };
    }

    private function createGame(GameResource $data, UserId $userId): void
    {
        $this->commandBus->send(new CreateGameCommand(
            gameId: new GameId(Uuid::v4()->toRfc4122()),
            playerId: new PlayerId(Uuid::v4()->toRfc4122()),
            name: new GameName($data->name),
            userId: $userId,
            createdAt: Timestamp::now()
        ));
    }

    private function startGame(string $gameId): void
    {
        try {
            $this->commandBus->send(new StartGameCommand(
                gameId: new GameId($gameId),
                startedAt: Timestamp::now()
            ));
        } catch (AggregateNotFoundException $e) {
            throw new NotFoundHttpException("Game with ID $gameId not found");
        }
    }

    private function joinGame(string $gameId, GameResource $data, UserId $userId): void
    {
        try {
            $this->commandBus->send(new JoinGameCommand(
                gameId: new GameId($gameId),
                playerId: new PlayerId($data->playerId),
                userId: $userId,
                joinedAt: Timestamp::now()
            ));
        } catch (AggregateNotFoundException $e) {
            throw new NotFoundHttpException("Game with ID $gameId not found");
        }
    }
}
