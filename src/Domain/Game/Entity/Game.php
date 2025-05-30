<?php

namespace App\Domain\Game\Entity;

use App\Domain\Game\Event\GameCreated;
use App\Domain\Game\Event\GameEnded;
use App\Domain\Game\Event\GameStarted;
use App\Domain\Game\Event\PlayerJoinedGame;
use App\Domain\Game\Event\PlayerLeftGame;
use App\Domain\Game\Event\TurnChanged;
use App\Domain\Game\Exception\InvalidGameDataException;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Game\ValueObject\GameSettings;
use App\Domain\Game\ValueObject\GameStatus;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\Trait\DomainEventsTrait;
use DateTimeImmutable;

/**
 * Game - Aggregate Root for game domain
 *
 * Central entity that manages game state, coordinates domain events,
 * and enforces business rules for game lifecycle. Acts as the single
 * source of truth for game state and player management.
 *
 * Uses DomainEventsTrait for consistent event management.
 *
 * Responsibilities:
 * - Game lifecycle management (create, start, pause, end)
 * - Player management (join, leave, validation)
 * - Turn management and coordination
 * - Domain event emission
 * - Business rule enforcement
 */
class Game
{
    use DomainEventsTrait;

    private array $players = [];
    private ?PlayerId $currentPlayerId = null;
    private int $currentTurnNumber = 1;
    private readonly DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $startedAt = null;
    private ?DateTimeImmutable $endedAt = null;

    private function __construct(
        private readonly GameId $id,
        private readonly string $name,
        private readonly GameSettings $settings,
        private GameStatus $status = GameStatus::WAITING_FOR_PLAYERS
    ) {
        $this->createdAt = new DateTimeImmutable();
        $this->recordDomainEvent(new GameCreated($this->id, $this->name));
    }

    // =================== FACTORY METHODS ===================

    /**
     * Creates a new game with default settings
     */
    public static function create(GameId $id, string $name, ?GameSettings $settings = null): self
    {
        if (empty(trim($name))) {
            throw InvalidGameDataException::emptyGameName();
        }

        if (strlen($name) > 100) {
            throw InvalidGameDataException::gameNameTooLong(100);
        }

        return new self($id, $name, $settings ?? GameSettings::createDefault());
    }

    /**
     * Creates a quick play game
     */
    public static function createQuickPlay(GameId $id, string $name): self
    {
        return new self($id, $name, GameSettings::createQuickPlay());
    }

    /**
     * Creates a custom game with specific settings
     */
    public static function createCustom(GameId $id, string $name, GameSettings $settings): self
    {
        return new self($id, $name, $settings);
    }

    // =================== GAME LIFECYCLE ===================

    /**
     * Starts the game if conditions are met
     */
    public function start(): void
    {
        if (!$this->canStart()) {
            throw InvalidGameDataException::gameCannotStart($this->status, count($this->players), $this->settings->minPlayers);
        }

        $this->status = GameStatus::IN_PROGRESS;
        $this->startedAt = new DateTimeImmutable();
        
        // Set first player as current player
        if (!empty($this->players)) {
            $this->currentPlayerId = reset($this->players);
        }

        $this->recordDomainEvent(new GameStarted($this->id));
    }

    /**
     * Pauses the game
     */
    public function pause(): void
    {
        if ($this->status !== GameStatus::IN_PROGRESS) {
            throw InvalidGameDataException::gameCannotBePaused($this->status);
        }

        $this->status = GameStatus::PAUSED;
    }

    /**
     * Resumes the game from paused state
     */
    public function resume(): void
    {
        if ($this->status !== GameStatus::PAUSED) {
            throw InvalidGameDataException::gameCannotBeResumed($this->status);
        }

        $this->status = GameStatus::IN_PROGRESS;
    }

    /**
     * Ends the game
     */
    public function end(?string $reason = null): void
    {
        if ($this->status === GameStatus::ENDED) {
            return; // Already ended
        }

        $this->status = GameStatus::ENDED;
        $this->endedAt = new DateTimeImmutable();
        $this->recordDomainEvent(new GameEnded($this->id, reason: $reason));
    }

    // =================== PLAYER MANAGEMENT ===================

    /**
     * Adds a player to the game
     */
    public function addPlayer(PlayerId $playerId): void
    {
        if ($this->hasPlayer($playerId)) {
            throw InvalidGameDataException::playerAlreadyInGame($playerId);
        }

        if (count($this->players) >= $this->settings->maxPlayers) {
            throw InvalidGameDataException::gameIsFull($this->settings->maxPlayers);
        }

        if (!$this->canAcceptPlayers()) {
            throw InvalidGameDataException::gameCannotAcceptPlayers($this->status);
        }

        $this->players[] = $playerId;
        $this->recordDomainEvent(new PlayerJoinedGame($this->id, $playerId));

        // Auto-start if enabled and conditions are met
        if ($this->settings->autoStart && $this->canStart()) {
            $this->start();
        }
    }

    /**
     * Removes a player from the game
     */
    public function removePlayer(PlayerId $playerId): void
    {
        if (!$this->hasPlayer($playerId)) {
            throw InvalidGameDataException::playerNotInGame($playerId);
        }

        $this->players = array_values(array_filter(
            $this->players, 
            fn(PlayerId $id) => !$id->equals($playerId)
        ));

        $this->recordDomainEvent(new PlayerLeftGame($this->id, $playerId));

        // Handle current player removal
        if ($this->currentPlayerId?->equals($playerId)) {
            $this->advanceToNextPlayer();
        }

        // End game if not enough players remaining
        if ($this->status === GameStatus::IN_PROGRESS && count($this->players) < $this->settings->minPlayers) {
            $this->end('Not enough players remaining');
        }
    }

    // =================== TURN MANAGEMENT ===================

    /**
     * Advances to the next player's turn
     */
    public function nextTurn(): void
    {
        if (!$this->isInProgress()) {
            throw InvalidGameDataException::gameNotInProgress($this->status);
        }

        if (empty($this->players)) {
            throw InvalidGameDataException::noPlayersInGame();
        }

        $this->advanceToNextPlayer();
        $this->currentTurnNumber++;

        // Check for max turns limit
        if ($this->settings->hasMaxTurns() && $this->currentTurnNumber > $this->settings->maxTurns) {
            $this->end('Maximum turn limit reached');
            return;
        }

        $this->recordDomainEvent(new TurnChanged(
            $this->id,
            $this->currentPlayerId,
            $this->currentTurnNumber
        ));
    }

    /**
     * Sets specific player as current player (for admin override)
     */
    public function setCurrentPlayer(PlayerId $playerId): void
    {
        if (!$this->hasPlayer($playerId)) {
            throw InvalidGameDataException::playerNotInGame($playerId);
        }

        if (!$this->isInProgress()) {
            throw InvalidGameDataException::gameNotInProgress($this->status);
        }

        $this->currentPlayerId = $playerId;
        $this->recordDomainEvent(new TurnChanged(
            $this->id,
            $this->currentPlayerId,
            $this->currentTurnNumber
        ));
    }

    // =================== QUERY METHODS ===================

    public function getId(): GameId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSettings(): GameSettings
    {
        return $this->settings;
    }

    public function getStatus(): GameStatus
    {
        return $this->status;
    }

    public function getPlayers(): array
    {
        return $this->players;
    }

    public function getCurrentPlayerId(): ?PlayerId
    {
        return $this->currentPlayerId;
    }

    public function getCurrentTurnNumber(): int
    {
        return $this->currentTurnNumber;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getEndedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function getPlayerCount(): int
    {
        return count($this->players);
    }

    // =================== STATE CHECKS ===================

    public function canStart(): bool
    {
        return $this->status === GameStatus::WAITING_FOR_PLAYERS &&
               count($this->players) >= $this->settings->minPlayers;
    }

    public function canAcceptPlayers(): bool
    {
        return $this->status->canAcceptPlayers() &&
               count($this->players) < $this->settings->maxPlayers;
    }

    public function isInProgress(): bool
    {
        return $this->status->isActive();
    }

    public function isFinished(): bool
    {
        return $this->status->isFinished();
    }

    public function hasPlayer(PlayerId $playerId): bool
    {
        return array_any($this->players, fn(PlayerId $id) => $id->equals($playerId));
    }

    public function isCurrentPlayer(PlayerId $playerId): bool
    {
        return $this->currentPlayerId?->equals($playerId) ?? false;
    }

    // =================== SERIALIZATION ===================

    public function toArray(): array
    {
        return [
            'id' => $this->id->value,
            'name' => $this->name,
            'status' => $this->status->value,
            'settings' => $this->settings->toArray(),
            'players' => array_map(fn(PlayerId $id) => $id->value, $this->players),
            'currentPlayerId' => $this->currentPlayerId?->value,
            'currentTurnNumber' => $this->currentTurnNumber,
            'createdAt' => $this->createdAt->format('c'),
            'startedAt' => $this->startedAt?->format('c'),
            'endedAt' => $this->endedAt?->format('c'),
            'playerCount' => $this->getPlayerCount()
        ];
    }

    public static function fromArray(array $data): self
    {
        $game = new self(
            new GameId($data['id']),
            $data['name'],
            GameSettings::fromArray($data['settings']),
            GameStatus::from($data['status'])
        );

        // Restore state
        $game->players = array_map(fn(string $id) => new PlayerId($id), $data['players']);
        $game->currentPlayerId = $data['currentPlayerId'] ? new PlayerId($data['currentPlayerId']) : null;
        $game->currentTurnNumber = $data['currentTurnNumber'];
        
        if ($data['startedAt']) {
            $game->startedAt = new DateTimeImmutable($data['startedAt']);
        }
        
        if ($data['endedAt']) {
            $game->endedAt = new DateTimeImmutable($data['endedAt']);
        }

        return $game;
    }

    // =================== PRIVATE HELPERS ===================

    private function advanceToNextPlayer(): void
    {
        if (empty($this->players)) {
            $this->currentPlayerId = null;
            return;
        }

        if ($this->currentPlayerId === null) {
            $this->currentPlayerId = reset($this->players);
            return;
        }

        // Find current player index manually since we need object comparison
        $currentIndex = null;
        foreach ($this->players as $index => $playerId) {
            if ($playerId->equals($this->currentPlayerId)) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null) {
            // Current player not found, start with first player
            $this->currentPlayerId = reset($this->players);
        } else {
            // Move to next player (circular)
            $nextIndex = ($currentIndex + 1) % count($this->players);
            $this->currentPlayerId = $this->players[$nextIndex];
        }
    }
}
