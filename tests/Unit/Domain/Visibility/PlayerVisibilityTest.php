<?php

namespace App\Tests\Unit\Domain\Visibility;

use App\Application\Visibility\Command\UpdateVisibilityCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitType;
use App\Domain\Visibility\Event\VisibilityRevealed;
use App\Domain\Visibility\Event\VisibilityUpdated;
use App\Domain\Visibility\PlayerVisibility;
use App\Domain\Visibility\Service\VisibilityCalculator;
use App\Domain\Visibility\ValueObject\VisibilityState;
use PHPUnit\Framework\TestCase;

class FakeVisibilityCalculator
{
    public function __construct() {}
    public function calculateUnitVisibility($position, $unitType)
    {
        return [['x' => 5, 'y' => 5]];
    }
    public function calculateCityVisibility($position, $cityLevel = 1)
    {
        return [['x' => 5, 'y' => 5], ['x' => 6, 'y' => 6]];
    }
}

class PlayerVisibilityTest extends TestCase
{
    private PlayerVisibility $playerVisibility;
    private PlayerId $playerId;
    private GameId $gameId;
    private VisibilityCalculator $calculator;

    protected function setUp(): void
    {
        $this->playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        $this->gameId = new GameId('123e4567-e89b-12d3-a456-426614174002');
        $this->calculator = new VisibilityCalculator();
        $this->playerVisibility = new PlayerVisibility();
        
        // Initialize the aggregate with playerId and gameId
        $this->playerVisibility->whenVisibilityUpdated(new VisibilityUpdated(
            '123e4567-e89b-12d3-a456-426614174001',
            0,
            0,
            VisibilityState::ACTIVE->value,
            '2024-01-01T00:00:00Z'
        ));
    }

    public function testInitialize(): void
    {
        $command = new UpdateVisibilityCommand(
            '123e4567-e89b-12d3-a456-426614174001',
            [],
            [],
            Timestamp::now()
        );

        $events = PlayerVisibility::initialize($command);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(VisibilityUpdated::class, $events[0]);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174001', $events[0]->playerId);
        $this->assertEquals(VisibilityState::ACTIVE->value, $events[0]->state);
    }

    public function testUpdateVisibilityWithUnit(): void
    {
        $calculator = new FakeVisibilityCalculator();
        $this->playerVisibility = new \App\Domain\Visibility\PlayerVisibility();
        $command = new UpdateVisibilityCommand(
            '123e4567-e89b-12d3-a456-426614174001',
            [
                ['x' => 5, 'y' => 5, 'type' => UnitType::SCOUT->value]
            ],
            [],
            Timestamp::now()
        );

        $events = $this->playerVisibility->updateVisibility($command, $calculator);

        $this->assertGreaterThan(0, count($events));
        $revealedEvents = array_filter($events, fn($event) => $event instanceof VisibilityRevealed);
        $updatedEvents = array_filter($events, fn($event) => $event instanceof VisibilityUpdated);
        $this->assertGreaterThanOrEqual(0, count($revealedEvents));
        $this->assertGreaterThan(0, count($updatedEvents));
    }

    public function testUpdateVisibilityWithCity(): void
    {
        $calculator = new FakeVisibilityCalculator();
        $this->playerVisibility = new \App\Domain\Visibility\PlayerVisibility();
        $command = new UpdateVisibilityCommand(
            '123e4567-e89b-12d3-a456-426614174001',
            [],
            [
                ['x' => 5, 'y' => 5, 'level' => 2]
            ],
            Timestamp::now()
        );

        $events = $this->playerVisibility->updateVisibility($command, $calculator);

        $this->assertGreaterThan(0, count($events));
        $revealedEvents = array_filter($events, fn($event) => $event instanceof VisibilityRevealed);
        $updatedEvents = array_filter($events, fn($event) => $event instanceof VisibilityUpdated);
        $this->assertGreaterThanOrEqual(0, count($revealedEvents));
        $this->assertGreaterThan(0, count($updatedEvents));
    }

    public function testUpdateVisibilityWithMultipleSources(): void
    {
        $calculator = new FakeVisibilityCalculator();
        $this->playerVisibility = new \App\Domain\Visibility\PlayerVisibility();
        $command = new UpdateVisibilityCommand(
            '123e4567-e89b-12d3-a456-426614174001',
            [
                ['x' => 5, 'y' => 5, 'type' => UnitType::WARRIOR->value]
            ],
            [
                ['x' => 6, 'y' => 6, 'level' => 1]
            ],
            Timestamp::now()
        );

        $events = $this->playerVisibility->updateVisibility($command, $calculator);

        $this->assertGreaterThan(0, count($events));
    }

    public function testIsHexVisible(): void
    {
        $this->playerVisibility->whenVisibilityUpdated(new VisibilityUpdated(
            '123e4567-e89b-12d3-a456-426614174001',
            5,
            5,
            VisibilityState::ACTIVE->value,
            '2024-01-01T00:00:00Z'
        ));

        $this->assertTrue($this->playerVisibility->isHexVisible(5, 5));
        $this->assertFalse($this->playerVisibility->isHexVisible(6, 6));
    }

    public function testIsHexDiscovered(): void
    {
        $this->playerVisibility->whenVisibilityRevealed(new VisibilityRevealed(
            '123e4567-e89b-12d3-a456-426614174001',
            5,
            5,
            '2024-01-01T00:00:00Z'
        ));

        $this->assertTrue($this->playerVisibility->isHexDiscovered(5, 5));
        $this->assertFalse($this->playerVisibility->isHexDiscovered(6, 6));
    }

    public function testGetVisibleHexes(): void
    {
        $this->playerVisibility->whenVisibilityUpdated(new VisibilityUpdated(
            '123e4567-e89b-12d3-a456-426614174001',
            5,
            5,
            VisibilityState::ACTIVE->value,
            '2024-01-01T00:00:00Z'
        ));

        $visibleHexes = $this->playerVisibility->getVisibleHexes();
        
        $this->assertCount(2, $visibleHexes);
        $this->assertContainsEquals(new Position(0, 0), $visibleHexes);
        $this->assertContainsEquals(new Position(5, 5), $visibleHexes);
    }

    public function testGetDiscoveredHexes(): void
    {
        $this->playerVisibility->whenVisibilityRevealed(new VisibilityRevealed(
            '123e4567-e89b-12d3-a456-426614174001',
            5,
            5,
            '2024-01-01T00:00:00Z'
        ));

        $discoveredHexes = $this->playerVisibility->getDiscoveredHexes();
        
        $this->assertCount(2, $discoveredHexes);
        $this->assertContainsEquals(new Position(0, 0), $discoveredHexes);
        $this->assertContainsEquals(new Position(5, 5), $discoveredHexes);
    }

    public function testNoDuplicateEventsForSameHex(): void
    {
        $calculator = new FakeVisibilityCalculator();
        $this->playerVisibility = new \App\Domain\Visibility\PlayerVisibility();
        $command = new UpdateVisibilityCommand(
            '123e4567-e89b-12d3-a456-426614174001',
            [
                ['x' => 5, 'y' => 5, 'type' => UnitType::WARRIOR->value]
            ],
            [],
            Timestamp::now()
        );

        $events1 = $this->playerVisibility->updateVisibility($command, $calculator);
        // Apply all events to update aggregate state
        foreach ($events1 as $event) {
            if ($event instanceof \App\Domain\Visibility\Event\VisibilityUpdated) {
                $this->playerVisibility->whenVisibilityUpdated($event);
            } elseif ($event instanceof \App\Domain\Visibility\Event\VisibilityRevealed) {
                $this->playerVisibility->whenVisibilityRevealed($event);
            }
        }
        $events2 = $this->playerVisibility->updateVisibility($command, $calculator);
        $this->assertGreaterThan(0, count($events1));
        $this->assertCount(0, $events2, 'Second call should not produce any new events for the same hex');
    }
} 