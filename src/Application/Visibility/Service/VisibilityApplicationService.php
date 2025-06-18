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
        private QueryBus   $queryBus
    )
    {
    }

    public function updatePlayerVisibility(
        PlayerId $playerId,
        array    $units,
        array    $cities
    ): void
    {
        $unitPositions = array_map(
            function($unit) {
                if (is_object($unit) && isset($unit->position) && $unit->position instanceof \App\Domain\Shared\ValueObject\Position) {
                    return [
                        'x' => $unit->position->x,
                        'y' => $unit->position->y,
                        'type' => is_object($unit->type) ? $unit->type->value : $unit->type
                    ];
                } elseif (is_object($unit) && isset($unit->position['x'])) {
                    return [
                        'x' => $unit->position['x'],
                        'y' => $unit->position['y'],
                        'type' => is_object($unit->type) ? $unit->type->value : $unit->type
                    ];
                } elseif (is_array($unit) && isset($unit['position'])) {
                    return [
                        'x' => $unit['position']['x'],
                        'y' => $unit['position']['y'],
                        'type' => is_object($unit['type']) ? $unit['type']->value : $unit['type']
                    ];
                } elseif (is_array($unit) && isset($unit['x'])) {
                    return [
                        'x' => $unit['x'],
                        'y' => $unit['y'],
                        'type' => is_object($unit['type']) ? $unit['type']->value : $unit['type']
                    ];
                }
                throw new \InvalidArgumentException('Invalid unit structure');
            },
            $units
        );

        $cityPositions = array_map(
            function($city) {
                if (is_object($city) && isset($city->position) && $city->position instanceof \App\Domain\Shared\ValueObject\Position) {
                    return [
                        'x' => $city->position->x,
                        'y' => $city->position->y,
                        'level' => $city->level ?? 1
                    ];
                } elseif (is_object($city) && isset($city->position['x'])) {
                    return [
                        'x' => $city->position['x'],
                        'y' => $city->position['y'],
                        'level' => $city->level ?? 1
                    ];
                } elseif (is_array($city) && isset($city['position'])) {
                    return [
                        'x' => $city['position']['x'],
                        'y' => $city['position']['y'],
                        'level' => $city['level'] ?? 1
                    ];
                } elseif (is_array($city) && isset($city['x'])) {
                    return [
                        'x' => $city['x'],
                        'y' => $city['y'],
                        'level' => $city['level'] ?? 1
                    ];
                }
                throw new \InvalidArgumentException('Invalid city structure');
            },
            $cities
        );

        $command = new UpdateVisibilityCommand(
            (string)$playerId,
            $unitPositions,
            $cityPositions,
            Timestamp::now()
        );

        $this->commandBus->send($command);
    }

    public function getPlayerVisibility(PlayerId $playerId): array
    {
        $query = new GetPlayerVisibilityQuery($playerId);
        return $this->queryBus->send($query);
    }

    public function getGameVisibility(GameId $gameId): array
    {
        $query = new GetGameVisibilityQuery($gameId);
        return $this->queryBus->send($query);
    }

    public function isHexVisibleForPlayer(int $x, int $y, PlayerId $playerId): bool
    {
        $visibility = $this->getPlayerVisibility($playerId);

        foreach ($visibility as $hex) {
            if ($hex->x === $x && $hex->y === $y && $hex->state === 'active') {
                return true;
            }
        }

        return false;
    }

    public function isHexDiscoveredForPlayer(int $x, int $y, PlayerId $playerId): bool
    {
        $visibility = $this->getPlayerVisibility($playerId);

        foreach ($visibility as $hex) {
            if ($hex->x === $x && $hex->y === $y) {
                return true;
            }
        }

        return false;
    }
}
