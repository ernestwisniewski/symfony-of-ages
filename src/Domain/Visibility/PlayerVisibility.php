<?php

namespace App\Domain\Visibility;

use App\Application\Visibility\Command\UpdateVisibilityCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Unit\ValueObject\UnitType;
use App\Domain\Visibility\Event\VisibilityRevealed;
use App\Domain\Visibility\Event\VisibilityUpdated;
use App\Domain\Visibility\Service\VisibilityCalculator;
use App\Domain\Visibility\ValueObject\VisibilityState;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
final class PlayerVisibility
{
    use WithAggregateVersioning;

    #[Identifier]
    private PlayerId $playerId;
    private GameId $gameId;
    private array $visibleHexes = [];
    private array $discoveredHexes = [];

    #[CommandHandler]
    public static function initialize(UpdateVisibilityCommand $command): array
    {
        return [
            new VisibilityUpdated(
                $command->playerId,
                $command->gameId,
                0,
                0,
                VisibilityState::ACTIVE->value,
                $command->updatedAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function updateVisibility(UpdateVisibilityCommand $command, VisibilityCalculator $calculator): array
    {
        $events = [];
        $newActiveHexes = [];

        foreach ($command->unitPositions as $unitData) {
            $position = new Position($unitData['x'], $unitData['y']);
            $unitType = UnitType::from($unitData['type']);
            $visibleHexes = $calculator->calculateUnitVisibility($position, $unitType);
            $newActiveHexes = array_merge($newActiveHexes, $visibleHexes);
        }

        foreach ($command->cityPositions as $cityData) {
            $position = new Position($cityData['x'], $cityData['y']);
            $cityLevel = $cityData['level'] ?? 1;
            $visibleHexes = $calculator->calculateCityVisibility($position, $cityLevel);
            $newActiveHexes = array_merge($newActiveHexes, $visibleHexes);
        }

        $newActiveHexes = array_unique($newActiveHexes, SORT_REGULAR);

        foreach ($newActiveHexes as $hex) {
            $hexKey = $hex->x . ':' . $hex->y;
            
            if (!isset($this->visibleHexes[$hexKey])) {
                if (!isset($this->discoveredHexes[$hexKey])) {
                    $events[] = new VisibilityRevealed(
                        (string)$this->playerId,
                        (string)$this->gameId,
                        $hex->x,
                        $hex->y,
                        $command->updatedAt->format()
                    );
                }
                $events[] = new VisibilityUpdated(
                    (string)$this->playerId,
                    (string)$this->gameId,
                    $hex->x,
                    $hex->y,
                    VisibilityState::ACTIVE->value,
                    $command->updatedAt->format()
                );
            }
        }

        return $events;
    }

    #[EventSourcingHandler]
    public function whenVisibilityUpdated(VisibilityUpdated $event): void
    {
        $this->playerId = new PlayerId($event->playerId);
        $this->gameId = new GameId($event->gameId);
        
        $hexKey = $event->x . ':' . $event->y;
        $state = VisibilityState::from($event->state);
        
        if ($state->isActive()) {
            $this->visibleHexes[$hexKey] = new Position($event->x, $event->y);
        } elseif ($state->isDiscovered()) {
            $this->discoveredHexes[$hexKey] = new Position($event->x, $event->y);
        }
    }

    #[EventSourcingHandler]
    public function whenVisibilityRevealed(VisibilityRevealed $event): void
    {
        $hexKey = $event->x . ':' . $event->y;
        $this->discoveredHexes[$hexKey] = new Position($event->x, $event->y);
    }

    public function isHexVisible(int $x, int $y): bool
    {
        $hexKey = $x . ':' . $y;
        return isset($this->visibleHexes[$hexKey]);
    }

    public function isHexDiscovered(int $x, int $y): bool
    {
        $hexKey = $x . ':' . $y;
        return isset($this->discoveredHexes[$hexKey]);
    }

    public function getVisibleHexes(): array
    {
        return array_values($this->visibleHexes);
    }

    public function getDiscoveredHexes(): array
    {
        return array_values($this->discoveredHexes);
    }
} 