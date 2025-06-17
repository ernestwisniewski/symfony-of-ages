<?php

declare(strict_types=1);

namespace App\UI\Game\LiveComponent;

use App\Application\Game\Command\StartGameCommand;
use App\Application\Game\Query\GetAllGamesQuery;
use App\Application\Game\Query\GetUserGamesQuery;
use App\Domain\Game\Game;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameStatus;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Shared\ValueObject\UserId;
use App\UI\Game\ViewModel\GameView;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class GameListComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public array $games = [];

    #[LiveProp(writable: true)]
    public int $minPlayers = Game::MIN_PLAYERS;

    #[LiveProp(writable: true)]
    public int $maxPlayers = Game::MAX_PLAYERS;

    #[LiveProp(writable: true)]
    public bool $showUserGames = false;

    public function __construct(
        private readonly QueryBus   $queryBus,
        private readonly CommandBus $commandBus,
        private readonly Security   $security
    )
    {
    }

    /**
     * Initialize component with games data
     */
    public function mount(bool $showUserGames = false): void
    {
        $this->showUserGames = $showUserGames;
        $this->minPlayers = Game::MIN_PLAYERS;
        $this->maxPlayers = Game::MAX_PLAYERS;
        $this->loadGames();
    }

    #[LiveAction('startGame')]
    public function startGame(#[LiveArg] string $gameId): void
    {
        $this->commandBus->send(new StartGameCommand(
            gameId: new GameId($gameId),
            startedAt: Timestamp::now()
        ));

        $this->loadGames();
    }

    #[LiveAction('refreshGames')]
    public function refreshGames(): void
    {
        $this->loadGames();
    }

    #[LiveAction('toggleUserGames')]
    public function toggleUserGames(): void
    {
        $this->showUserGames = !$this->showUserGames;
        $this->loadGames();
    }

    #[LiveListener('game:started')]
    public function onGameStarted(): void
    {
        $this->loadGames();
    }

    #[LiveListener('game:joined')]
    public function onGameJoined(): void
    {
        $this->loadGames();
    }

    private function loadGames(): void
    {
        if ($this->showUserGames) {
            /** @var GameView[] $games */
            $games = $this->queryBus->send(new GetUserGamesQuery(
                new UserId($this->security->getUser()->getId())
            ));
        } else {
            /** @var GameView[] $games */
            $games = $this->queryBus->send(new GetAllGamesQuery());
        }

        $this->games = array_map(fn(GameView $game) => $this->formatGameData($game), $games);
    }

    private function formatGameData(GameView $game): array
    {
        return [
            'id' => $game->id,
            'name' => $game->name,
            'status' => $game->status,
            'players' => $game->players,
            'userId' => $game->userId,
            'playerCount' => count($game->players),
            'createdAt' => $game->createdAt,
            'startedAt' => $game->startedAt,
            'activePlayer' => $game->activePlayer,
            'currentTurn' => $game->currentTurn,
            'canStart' => $this->canGameStart($game),
            'playersNeeded' => $this->getPlayersNeeded($game),
            'isStarted' => $game->status !== GameStatus::WAITING_FOR_PLAYERS->value
        ];
    }

    private function canGameStart(GameView $game): bool
    {
        return $game->status === GameStatus::WAITING_FOR_PLAYERS->value && count($game->players) >= $this->minPlayers;
    }

    private function getPlayersNeeded(GameView $game): int
    {
        return max(0, $this->minPlayers - count($game->players));
    }

    public function getGamesCount(): int
    {
        return count($this->games);
    }

    public function getWaitingGamesCount(): int
    {
        return count(array_filter($this->games, fn($game) => $game['status'] === GameStatus::WAITING_FOR_PLAYERS->value));
    }

    public function getActiveGamesCount(): int
    {
        return count(array_filter($this->games, fn($game) => $game['status'] === GameStatus::IN_PROGRESS->value));
    }

    public function hasGames(): bool
    {
        return !empty($this->games);
    }

    public function getStartableGames(): array
    {
        return array_filter($this->games, fn($game) => $game['canStart']);
    }

    public function getIncompleteGames(): array
    {
        return array_filter($this->games, fn($game) => !$game['canStart'] && !$game['isStarted']);
    }
}
