<?php

namespace App\Domain\Visibility;

use App\Application\Visibility\Command\UpdateVisibilityCommand;
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
    private array $visibleHexes = [];
    private array $discoveredHexes = [];

    #[CommandHandler]
    public static function initialize(UpdateVisibilityCommand $command): array
    {
        return [
            new VisibilityUpdated(
                $command->playerId,
                0,
                0,
                VisibilityState::ACTIVE->value,
                $command->updatedAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function updateVisibility(UpdateVisibilityCommand $command, $calculator): array
    {
        $events = [];

        // Update visibility for units
        foreach ($command->unitPositions as $unitPosition) {
            if (isset($unitPosition['x']) && isset($unitPosition['y'])) {
                $position = new Position($unitPosition['x'], $unitPosition['y']);
                $unitType = UnitType::from($unitPosition['type']);
            } elseif (is_object($unitPosition) && isset($unitPosition->position) && $unitPosition->position instanceof Position) {
                $position = $unitPosition->position;
                $unitType = is_object($unitPosition->type) ? $unitPosition->type : UnitType::from($unitPosition->type);
            } else {
                throw new \InvalidArgumentException('Invalid unitPosition structure');
            }
            $visibleHexes = $calculator->calculateUnitVisibility($position, $unitType);

            foreach ($visibleHexes as $hex) {
                $x = is_array($hex) ? $hex['x'] : $hex->x;
                $y = is_array($hex) ? $hex['y'] : $hex->y;
                $events[] = new VisibilityUpdated(
                    $command->playerId,
                    $x,
                    $y,
                    VisibilityState::ACTIVE->value,
                    $command->updatedAt->format()
                );
            }
        }

        // Update visibility for cities
        foreach ($command->cityPositions as $cityPosition) {
            if (isset($cityPosition['x']) && isset($cityPosition['y'])) {
                $position = new Position($cityPosition['x'], $cityPosition['y']);
                $cityLevel = $cityPosition['level'] ?? 1;
            } elseif (is_object($cityPosition) && isset($cityPosition->position) && $cityPosition->position instanceof Position) {
                $position = $cityPosition->position;
                $cityLevel = $cityPosition->level ?? 1;
            } else {
                throw new \InvalidArgumentException('Invalid cityPosition structure');
            }
            $visibleHexes = $calculator->calculateCityVisibility($position, $cityLevel);

            foreach ($visibleHexes as $hex) {
                $x = is_array($hex) ? $hex['x'] : $hex->x;
                $y = is_array($hex) ? $hex['y'] : $hex->y;
                $events[] = new VisibilityUpdated(
                    $command->playerId,
                    $x,
                    $y,
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
        $hexKey = $event->x . ',' . $event->y;
        
        if ($event->state === VisibilityState::ACTIVE->value) {
            $this->visibleHexes[$hexKey] = true;
            $this->discoveredHexes[$hexKey] = true;
        } elseif ($event->state === VisibilityState::DISCOVERED->value) {
            $this->discoveredHexes[$hexKey] = true;
        }
    }

    #[EventSourcingHandler]
    public function whenVisibilityRevealed(VisibilityRevealed $event): void
    {
        $this->playerId = new PlayerId($event->playerId);
        $hexKey = $event->x . ',' . $event->y;
        $this->discoveredHexes[$hexKey] = true;
    }

    public function isHexVisible(int $x, int $y): bool
    {
        return isset($this->visibleHexes[$x . ',' . $y]);
    }

    public function isHexDiscovered(int $x, int $y): bool
    {
        return isset($this->discoveredHexes[$x . ',' . $y]);
    }

    public function getVisibleHexes(): array
    {
        return array_map(function ($key) {
            [$x, $y] = explode(',', $key);
            return new Position((int)$x, (int)$y);
        }, array_keys($this->visibleHexes));
    }

    public function getDiscoveredHexes(): array
    {
        return array_map(function ($key) {
            [$x, $y] = explode(',', $key);
            return new Position((int)$x, (int)$y);
        }, array_keys($this->discoveredHexes));
    }
}
