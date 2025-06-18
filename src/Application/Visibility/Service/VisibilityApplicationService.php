<?php

namespace App\Application\Visibility\Service;

use App\Application\Visibility\Command\UpdateVisibilityCommand;
use App\Application\Visibility\Query\GetGameVisibilityQuery;
use App\Application\Visibility\Query\GetPlayerVisibilityQuery;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;

readonly class VisibilityApplicationService
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus
    ) {
    }

    public function updatePlayerVisibility(
        PlayerId $playerId,
        GameId $gameId,
        array $units,
        array $cities
    ): void {
        $unitPositions = array_map(
            fn($unit) => [
                'x' => $unit->position['x'],
                'y' => $unit->position['y'],
                'type' => $unit->type
            ],
            $units
        );

        $cityPositions = array_map(
            fn($city) => [
                'x' => $city->position['x'],
                'y' => $city->position['y'],
                'level' => 1
            ],
            $cities
        );

        $command = new UpdateVisibilityCommand(
            (string)$playerId,
            (string)$gameId,
            $unitPositions,
            $cityPositions,
            Timestamp::now()
        );

        $this->commandBus->send($command);
    }

    public function getPlayerVisibility(PlayerId $playerId, GameId $gameId): array
    {
        $query = new GetPlayerVisibilityQuery($playerId, $gameId);
        return $this->queryBus->send($query);
    }

    public function getGameVisibility(GameId $gameId): array
    {
        $query = new GetGameVisibilityQuery($gameId);
        return $this->queryBus->send($query);
    }

    public function isHexVisibleForPlayer(int $x, int $y, PlayerId $playerId, GameId $gameId): bool
    {
        $visibility = $this->getPlayerVisibility($playerId, $gameId);
        
        foreach ($visibility as $hex) {
            if ($hex->x === $x && $hex->y === $y && $hex->state === 'active') {
                return true;
            }
        }
        
        return false;
    }

    public function isHexDiscoveredForPlayer(int $x, int $y, PlayerId $playerId, GameId $gameId): bool
    {
        $visibility = $this->getPlayerVisibility($playerId, $gameId);
        
        foreach ($visibility as $hex) {
            if ($hex->x === $x && $hex->y === $y) {
                return true;
            }
        }
        
        return false;
    }
} 