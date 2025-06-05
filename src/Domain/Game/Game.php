<?php

namespace App\Domain\Game;

use App\Application\Game\Command\CreateGameCommand;
use App\Application\Game\Command\EndTurnCommand;
use App\Application\Game\Command\JoinGameCommand;
use App\Application\Game\Command\StartGameCommand;
use App\Domain\Game\Event\GameWasCreated;
use App\Domain\Game\Event\GameWasStarted;
use App\Domain\Game\Event\PlayerEndedTurn;
use App\Domain\Game\Event\PlayerWasJoined;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Game\ValueObject\GameStatus;
use App\Domain\Game\ValueObject\Turn;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
class Game
{
    use WithAggregateVersioning;

    const int MIN_PLAYERS = 2;
    const int MAX_PLAYERS = 4;

    #[Identifier]
    public GameId $gameId {
        get {
            return $this->gameId;
        }
    }
    public GameName $name {
        get {
            return $this->name;
        }
    }
    public GameStatus $status {
        get {
            return $this->status;
        }
    }
    public array $players = [] {
        get {
            return $this->players;
        }
    }
    public Turn $currentTurn {
        get {
            return $this->currentTurn;
        }
    }
    public PlayerId $activePlayer {
        get {
            return $this->activePlayer;
        }
    }
    public Timestamp $createdAt {
        get {
            return $this->createdAt;
        }
    }
    public ?Timestamp $startedAt = null {
        get {
            return $this->startedAt;
        }
    }
    public ?Timestamp $currentTurnAt = null {
        get {
            return $this->currentTurnAt;
        }
    }

    #[CommandHandler]
    public static function create(CreateGameCommand $command): array
    {
        return [
            new GameWasCreated(
                $command->gameId->__toString(),
                $command->playerId->__toString(),
                $command->name,
                $command->createdAt->format()
            )];
    }

    #[CommandHandler]
    public function start(StartGameCommand $command): array
    {
        if (null !== $this->startedAt) {
            throw new \DomainException('Game was already started.');
        }

        if (count($this->players) < self::MIN_PLAYERS) {
            throw new \DomainException(
                sprintf('Minimum %d players required, but only %d joined.',
                    self::MIN_PLAYERS,
                    count($this->players)
                )
            );
        }

        return [
            new GameWasStarted(
                $command->gameId->__toString(),
                $command->startedAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function join(JoinGameCommand $command): array
    {
        if (null !== $this->startedAt) {
            throw new \DomainException('Game has already started.');
        }

        if ($this->hasPlayer($command->playerId)) {
            throw new \DomainException(
                sprintf('Player %s has already joined this game.', $command->playerId->__toString())
            );
        }

        if (count($this->players) >= self::MAX_PLAYERS) {
            throw new \DomainException(
                sprintf('Maximum %d players allowed, game is full.', self::MAX_PLAYERS)
            );
        }

        return [
            new PlayerWasJoined(
                $command->gameId->__toString(),
                $command->playerId->__toString(),
            )
        ];
    }

    #[CommandHandler]
    public function endTurn(EndTurnCommand $command): array
    {
        if (null === $this->startedAt) {
            throw new \DomainException('Game has not been started yet.');
        }

        if (false === $this->activePlayer->isEqual($command->playerId)) {
            throw new \DomainException(
                sprintf(
                    'It is not player %s\'s turn. Current active player is %s.',
                    $command->playerId->__toString(),
                    $this->activePlayer->__toString()
                )
            );
        }

        return [
            new PlayerEndedTurn(
                $command->gameId->__toString(),
                $command->playerId->__toString(),
                $command->endedAt->format()
            )
        ];
    }

    #[EventSourcingHandler]
    public function whenGameWasCreated(GameWasCreated $event): void
    {
        $this->gameId = new GameId($event->gameId);
        $this->name = new GameName($event->name);
        $this->status = GameStatus::WAITING_FOR_PLAYERS;
        $this->createdAt = Timestamp::fromString($event->createdAt);
        $this->addPlayer(new PlayerId($event->playerId));
        $this->activePlayer = new PlayerId($event->playerId);
        $this->currentTurn = new Turn(0);
    }

    #[EventSourcingHandler]
    public function whenGameWasStarted(GameWasStarted $event): void
    {
        $this->status = GameStatus::IN_PROGRESS;
        $this->activePlayer = $this->players[0];
        $this->startedAt = Timestamp::fromString($event->startedAt);
        $this->currentTurnAt = Timestamp::fromString($event->startedAt);
        $this->currentTurn = new Turn(1);
    }

    #[EventSourcingHandler]
    public function whenPlayerWasJoined(PlayerWasJoined $event): void
    {
        $this->addPlayer($event->playerId);
    }

    #[EventSourcingHandler]
    public function whenPlayerEndedTurn(PlayerEndedTurn $event): void
    {
        if ($this->isLastPlayer()) {
            $this->currentTurn = $this->currentTurn->next();
        }

        $this->activePlayer = $this->getNextPlayer();
        $this->currentTurnAt = Timestamp::fromString($event->endedAt);
    }

    private function hasPlayer(PlayerId $playerId): bool
    {
        return array_any($this->players, fn($player) => $player->isEqual($playerId));
    }

    private function addPlayer(string $player): void
    {
        $this->players = array_values(array_unique([
            ...$this->players,
            ...[new PlayerId($player)]
        ]));
    }

    private function getNextPlayer(): PlayerId
    {
        foreach ($this->players as $i => $player) {
            if ($player->isEqual($this->activePlayer)) {
                return $this->players[($i + 1) % count($this->players)];
            }
        }

        throw new \DomainException(
            sprintf(
                'Active player %s not found in player list. Game state is corrupted.',
                $this->activePlayer->__toString()
            )
        );
    }

    private function isLastPlayer(): bool
    {
        $lastIndex = count($this->players) - 1;
        $lastPlayer = $this->players[$lastIndex];

        return $lastPlayer->isEqual($this->activePlayer);
    }
}

