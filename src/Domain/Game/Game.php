<?php

namespace App\Domain\Game;

use App\Application\Game\Command\CreateGameCommand;
use App\Application\Game\Command\EndTurnCommand;
use App\Application\Game\Command\JoinGameCommand;
use App\Application\Game\Command\StartGameCommand;
use App\Application\Map\Command\GenerateMapCommand;
use App\Domain\Game\Event\GameWasCreated;
use App\Domain\Game\Event\GameWasStarted;
use App\Domain\Game\Event\MapWasGenerated;
use App\Domain\Game\Event\PlayerEndedTurn;
use App\Domain\Game\Event\PlayerWasJoined;
use App\Domain\Game\Policy\GameStartPolicy;
use App\Domain\Game\Policy\PlayerJoinPolicy;
use App\Domain\Game\Policy\TurnEndPolicy;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameName;
use App\Domain\Game\ValueObject\GameStatus;
use App\Domain\Game\ValueObject\Turn;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\UI\Map\ViewModel\MapTileView;
use DomainException;
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
    private GameId $gameId;
    private GameName $name;
    private GameStatus $status;
    private array $players = [];
    private Turn $currentTurn;
    private PlayerId $activePlayer;
    private array $mapTiles;
    private Timestamp $createdAt;
    private ?Timestamp $startedAt = null;
    private ?Timestamp $currentTurnAt = null;

    #[CommandHandler]
    public static function create(CreateGameCommand $command): array
    {
        return [
            new GameWasCreated(
                (string)$command->gameId,
                (string)$command->playerId,
                $command->name,
                $command->createdAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function start(StartGameCommand $command, GameStartPolicy $gameStartPolicy): array
    {
        $gameStartPolicy->validateStart(
            $this->gameId,
            count($this->players),
            $this->startedAt
        );

        return [
            new GameWasStarted(
                (string)$command->gameId,
                $command->startedAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function join(JoinGameCommand $command, PlayerJoinPolicy $playerJoinPolicy): array
    {
        $playerJoinPolicy->validateJoin(
            $this->gameId,
            $command->playerId,
            $this->players,
            $this->startedAt
        );

        return [
            new PlayerWasJoined(
                (string)$command->gameId,
                (string)$command->playerId,
            )
        ];
    }

    #[CommandHandler]
    public function endTurn(EndTurnCommand $command, TurnEndPolicy $turnEndPolicy): array
    {
        $turnEndPolicy->validateEndTurn(
            $this->gameId,
            $command->playerId,
            $this->activePlayer,
            $this->startedAt
        );

        return [
            new PlayerEndedTurn(
                (string)$command->gameId,
                (string)$command->playerId,
                $command->endedAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function generateMap(GenerateMapCommand $command): array
    {

        $tiles = array_map(
            fn(MapTileView $tile) => [
                'x' => $tile->x,
                'y' => $tile->y,
                'terrain' => $tile->terrain,
            ],
            $command->tiles,
        );

        return [
            new MapWasGenerated(
                gameId: $command->gameId,
                tiles: json_encode($tiles, true),
                width: $command->width,
                height: $command->height
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

    #[EventSourcingHandler]
    public function whenMapWasGenerated(MapWasGenerated $event): void
    {
        $this->mapTiles = json_decode($event->tiles, true);
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

        throw new DomainException(
            sprintf(
                'Active player %s not found in player list. Game state is corrupted.',
                (string)$this->activePlayer
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

