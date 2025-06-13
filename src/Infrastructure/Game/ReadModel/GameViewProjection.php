<?php

namespace App\Infrastructure\Game\ReadModel;

use App\Application\Game\Query\GetAllGamesQuery;
use App\Application\Game\Query\GetGameViewQuery;
use App\Application\Game\Query\GetUserGamesQuery;
use App\Domain\Game\Event\GameWasCreated;
use App\Domain\Game\Event\GameWasStarted;
use App\Domain\Game\Event\PlayerEndedTurn;
use App\Domain\Game\Event\PlayerWasJoined;
use App\Domain\Game\Game;
use App\Domain\Game\ValueObject\GameStatus;
use App\Infrastructure\Game\ReadModel\Doctrine\GameViewEntity;
use App\Infrastructure\Game\ReadModel\Doctrine\GameViewRepository;
use App\UI\Game\ViewModel\GameView;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[Projection("game_view", Game::class)]
readonly class GameViewProjection
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GameViewRepository     $gameViewRepository,
        private ObjectMapperInterface  $objectMapper
    )
    {
    }

    #[QueryHandler]
    public function getGameView(GetGameViewQuery $query): GameView
    {
        $gameView = $this->find($query->gameId);
        return $this->objectMapper->map($gameView, GameView::class);
    }

    #[QueryHandler]
    public function getUserGames(GetUserGamesQuery $query): array
    {
        $gameViewEntities = $this->gameViewRepository->createQueryBuilder('g')
            ->where('g.userId = :userId')
            ->setParameter('userId', $query->userId->id)
            ->getQuery()
            ->getResult();

        return array_map(
            fn(GameViewEntity $entity): GameView => $this->objectMapper->map($entity, GameView::class),
            $gameViewEntities
        );
    }

    #[QueryHandler]
    public function getAllGames(GetAllGamesQuery $query): array
    {
        $gameViewEntities = $this->gameViewRepository->findAll();

        return array_map(
            fn(GameViewEntity $entity): GameView => $this->objectMapper->map($entity, GameView::class),
            $gameViewEntities
        );
    }

    #[EventHandler]
    public function applyGameWasCreated(GameWasCreated $event): void
    {
        $gameView = new GameViewEntity(
            id: $event->gameId,
            name: $event->name,
            activePlayer: $event->playerId,
            currentTurn: 0,
            createdAt: new DateTimeImmutable($event->createdAt),
            status: GameStatus::WAITING_FOR_PLAYERS->value,
            players: [$event->playerId],
            userId: $event->userId
        );

        $this->save($gameView);
    }

    #[EventHandler]
    public function applyPlayerWasJoined(PlayerWasJoined $event): void
    {
        $gameView = $this->find($event->gameId);
        $players = $gameView->players;
        $players[] = $event->playerId;
        $gameView->players = array_values(array_unique($players));

        $this->saveChanges();
    }

    #[EventHandler]
    public function applyGameWasStarted(GameWasStarted $event): void
    {
        $gameView = $this->find($event->gameId);
        $gameView->startedAt = new DateTimeImmutable($event->startedAt);
        $gameView->currentTurn = 1;
        $gameView->currentTurnAt = new DateTimeImmutable($event->startedAt);
        $gameView->activePlayer = $gameView->players[0];
        $gameView->status = GameStatus::IN_PROGRESS->value;

        $this->saveChanges();
    }

    #[EventHandler]
    public function applyPlayerEndedTurn(PlayerEndedTurn $event): void
    {
        $gameView = $this->find($event->gameId);
        $players = $gameView->players;
        $current = $gameView->activePlayer;
        $index = array_search($current, $players, true);

        if ($index === false) {
            throw new RuntimeException("Active player {$current} not found in players list");
        }

        $next = $players[($index + 1) % count($players)];

        $gameView->activePlayer = $next;
        $gameView->currentTurnAt = new DateTimeImmutable($event->endedAt);

        if ($index === count($players) - 1) {
            $gameView->currentTurn = $gameView->currentTurn + 1;
        }

        $this->saveChanges();
    }

    private function find(string $gameId): GameViewEntity
    {
        $gameView = $this->gameViewRepository->find($gameId);

        if (!$gameView) {
            throw new RuntimeException("GameViewEntity for ID $gameId not found");
        }

        return $gameView;
    }

    private function save(GameViewEntity $gameView): void
    {
        $this->entityManager->persist($gameView);
        $this->entityManager->flush();
    }

    private function saveChanges(): void
    {
        $this->entityManager->flush();
    }
}
