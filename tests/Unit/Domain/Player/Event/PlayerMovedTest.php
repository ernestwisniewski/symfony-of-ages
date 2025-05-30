<?php

namespace Tests\Unit\Domain\Player\Event;

use App\Domain\Player\Event\PlayerMoved;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PlayerMoved domain event
 */
class PlayerMovedTest extends TestCase
{
    public function testCreatePlayerMovedEvent(): void
    {
        $playerId = new PlayerId('player_123');
        $fromPosition = new Position(5, 5);
        $toPosition = new Position(5, 6);
        $movementCost = 2;

        $event = new PlayerMoved($playerId, $fromPosition, $toPosition, $movementCost);

        $this->assertEquals($playerId, $event->getPlayerId());
        $this->assertEquals($fromPosition, $event->getFromPosition());
        $this->assertEquals($toPosition, $event->getToPosition());
        $this->assertEquals($movementCost, $event->getMovementCost());
        $this->assertInstanceOf(DateTimeImmutable::class, $event->getOccurredAt());
    }

    public function testEventOccurredAtIsSetToCurrentTime(): void
    {
        $playerId = new PlayerId('player_456');
        $fromPosition = new Position(0, 0);
        $toPosition = new Position(1, 1);
        $movementCost = 1;

        $beforeCreation = new DateTimeImmutable();
        $event = new PlayerMoved($playerId, $fromPosition, $toPosition, $movementCost);
        $afterCreation = new DateTimeImmutable();

        $occurredAt = $event->getOccurredAt();
        $this->assertGreaterThanOrEqual($beforeCreation, $occurredAt);
        $this->assertLessThanOrEqual($afterCreation, $occurredAt);
    }

    public function testGetDistanceCalculatesCorrectDistance(): void
    {
        $playerId = new PlayerId('player_789');
        $fromPosition = new Position(3, 3);
        $toPosition = new Position(3, 5); // 2 steps horizontally
        $movementCost = 3;

        $event = new PlayerMoved($playerId, $fromPosition, $toPosition, $movementCost);

        $this->assertEquals(2, $event->getDistance());
    }

    public function testGetDistanceForSamePosition(): void
    {
        $playerId = new PlayerId('player_same');
        $position = new Position(7, 7);
        $movementCost = 0;

        $event = new PlayerMoved($playerId, $position, $position, $movementCost);

        $this->assertEquals(0, $event->getDistance());
    }

    public function testGetDistanceForAdjacentPositions(): void
    {
        $playerId = new PlayerId('player_adjacent');
        $fromPosition = new Position(4, 4);
        $toPosition = new Position(4, 5); // Adjacent position
        $movementCost = 1;

        $event = new PlayerMoved($playerId, $fromPosition, $toPosition, $movementCost);

        $this->assertEquals(1, $event->getDistance());
    }

    public function testEventWithZeroMovementCost(): void
    {
        $playerId = new PlayerId('player_zero');
        $fromPosition = new Position(2, 2);
        $toPosition = new Position(2, 3);
        $movementCost = 0; // Free movement

        $event = new PlayerMoved($playerId, $fromPosition, $toPosition, $movementCost);

        $this->assertEquals(0, $event->getMovementCost());
        $this->assertEquals(1, $event->getDistance());
    }

    public function testEventWithHighMovementCost(): void
    {
        $playerId = new PlayerId('player_high_cost');
        $fromPosition = new Position(1, 1);
        $toPosition = new Position(1, 2);
        $movementCost = 5; // Expensive terrain

        $event = new PlayerMoved($playerId, $fromPosition, $toPosition, $movementCost);

        $this->assertEquals(5, $event->getMovementCost());
        $this->assertEquals(1, $event->getDistance());
    }

    public function testEventImmutability(): void
    {
        $playerId = new PlayerId('player_immutable');
        $fromPosition = new Position(8, 8);
        $toPosition = new Position(9, 8);
        $movementCost = 2;

        $event = new PlayerMoved($playerId, $fromPosition, $toPosition, $movementCost);

        // Verify that all returned objects are the same instances (immutable)
        $this->assertSame($playerId, $event->getPlayerId());
        $this->assertSame($fromPosition, $event->getFromPosition());
        $this->assertSame($toPosition, $event->getToPosition());
        $this->assertSame($movementCost, $event->getMovementCost());
        
        // OccurredAt should always return the same instance
        $occurredAt1 = $event->getOccurredAt();
        $occurredAt2 = $event->getOccurredAt();
        $this->assertSame($occurredAt1, $occurredAt2);
    }

    public function testEventWithComplexHexagonalMovement(): void
    {
        $playerId = new PlayerId('player_complex');
        $fromPosition = new Position(10, 10);
        $toPosition = new Position(12, 13); // Complex hexagonal movement
        $movementCost = 6; // Higher cost due to difficult terrain

        $event = new PlayerMoved($playerId, $fromPosition, $toPosition, $movementCost);

        $this->assertGreaterThan(0, $event->getDistance());
        $this->assertEquals($movementCost, $event->getMovementCost());
        
        // Movement cost and distance can be different (terrain affects cost)
        $this->assertNotEquals($event->getDistance(), $event->getMovementCost());
    }
} 