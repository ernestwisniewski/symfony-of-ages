<?php

namespace App\Tests\Unit\Domain\Player\Entity;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Event\PlayerMoved;
use App\Domain\Player\Exception\InvalidPlayerDataException;
use App\Domain\Player\ValueObject\MovementPoints;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Player entity after encapsulation improvements
 */
class PlayerTest extends TestCase
{
    private PlayerId $playerId;
    private Position $position;

    protected function setUp(): void
    {
        $this->playerId = new PlayerId('player_123');
        $this->position = new Position(10, 15);
    }

    public function testCanCreatePlayerWithValidData(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player(
            $this->playerId,
            $this->position,
            'Test Player',
            $movementPoints,
            0xFF6B6B
        );

        $this->assertEquals($this->playerId, $player->getId());
        $this->assertEquals($this->position, $player->getPosition());
        $this->assertEquals('Test Player', $player->getName());
        $this->assertEquals(3, $player->currentMovementPoints);
        $this->assertEquals(3, $player->maxMovementPoints);
        $this->assertEquals(0xFF6B6B, $player->getColor());
    }

    public function testCanCreatePlayerWithDefaultValues(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Default Player', $movementPoints);

        $this->assertEquals(3, $player->maxMovementPoints);
        $this->assertEquals(0xFF6B6B, $player->getColor());
    }

    public function testThrowsExceptionForEmptyName(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Player name cannot be empty');

        $movementPoints = MovementPoints::createFull(3);
        new Player($this->playerId, $this->position, '', $movementPoints);
    }

    public function testThrowsExceptionForWhitespaceOnlyName(): void
    {
        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Player name cannot be empty');

        $movementPoints = MovementPoints::createFull(3);
        new Player($this->playerId, $this->position, '   ', $movementPoints);
    }

    public function testThrowsExceptionForTooLongName(): void
    {
        $longName = str_repeat('a', 51);

        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Player name cannot exceed 50 characters');

        $movementPoints = MovementPoints::createFull(3);
        new Player($this->playerId, $this->position, $longName, $movementPoints);
    }

    public function testCanMoveToValidPosition(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);
        $newPosition = new Position(11, 15);

        $result = $player->moveTo($newPosition, 2);

        $this->assertTrue($result);
        $this->assertEquals($newPosition, $player->getPosition());
        $this->assertEquals(1, $player->currentMovementPoints); // 3 - 2 = 1
    }

    public function testCannotMoveWithInsufficientMovementPoints(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);
        $newPosition = new Position(11, 15);

        $result = $player->moveTo($newPosition, 5); // Need 5, have 3

        $this->assertFalse($result);
        $this->assertEquals($this->position, $player->getPosition()); // Position unchanged
        $this->assertEquals(3, $player->currentMovementPoints); // Movement points unchanged
    }

    public function testMoveToPublishesDomainEvent(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);
        $newPosition = new Position(11, 15);

        $player->moveTo($newPosition, 2);

        $events = $player->getDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PlayerMoved::class, $events[0]);

        $event = $events[0];
        $this->assertEquals($this->playerId, $event->playerId);
        $this->assertEquals($this->position, $event->fromPosition);
        $this->assertEquals($newPosition, $event->toPosition);
        $this->assertEquals(2, $event->movementCost);
    }

    public function testCanMoveToReturnsTrueForSufficientPoints(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);

        $this->assertTrue($player->canMoveTo(2));
        $this->assertTrue($player->canMoveTo(3));
    }

    public function testCanMoveToReturnsFalseForInsufficientPoints(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);

        $this->assertFalse($player->canMoveTo(4));
        $this->assertFalse($player->canMoveTo(10));
    }

    public function testStartNewTurnRestoresMovementPoints(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);
        
        // Spend some movement points
        $player->moveTo(new Position(11, 15), 2);
        $this->assertEquals(1, $player->currentMovementPoints);

        // Start new turn
        $player->startNewTurn();
        $this->assertEquals(3, $player->currentMovementPoints);
    }

    public function testCanContinueTurnReturnsTrueWithMovementPoints(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);

        $this->assertTrue($player->canContinueTurn());
    }

    public function testCanContinueTurnReturnsFalseWithoutMovementPoints(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);
        
        // Spend all movement points
        $player->moveTo(new Position(11, 15), 3);

        $this->assertFalse($player->canContinueTurn());
    }

    public function testCanChangeNameWithValidName(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Old Name', $movementPoints);

        $player->changeName('New Name');

        $this->assertEquals('New Name', $player->getName());
    }

    public function testCannotChangeNameToEmptyString(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Valid Name', $movementPoints);

        $this->expectException(InvalidPlayerDataException::class);
        $this->expectExceptionMessage('Player name cannot be empty');

        $player->changeName('');
    }

    public function testCanChangeColorWithValidColor(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);

        $player->changeColor(0x00FF00);

        $this->assertEquals(0x00FF00, $player->getColor());
    }

    public function testCannotChangeColorToNegativeValue(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);

        $this->expectException(InvalidPlayerDataException::class);

        $player->changeColor(-1);
    }

    public function testCannotChangeColorToValueAboveMaximum(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);

        $this->expectException(InvalidPlayerDataException::class);

        $player->changeColor(0x1000000); // 24-bit max is 0xFFFFFF
    }

    public function testClearDomainEventsRemovesAllEvents(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);
        
        // Generate some events
        $player->moveTo(new Position(11, 15), 1);
        $player->moveTo(new Position(12, 15), 1);

        $this->assertCount(2, $player->getDomainEvents());

        $player->clearDomainEvents();

        $this->assertCount(0, $player->getDomainEvents());
    }

    public function testToArrayReturnsCorrectData(): void
    {
        $movementPoints = MovementPoints::createFull(5);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints, 0x00FF00);

        $expected = [
            'id' => 'player_123',
            'name' => 'Test Player',
            'position' => ['row' => 10, 'col' => 15],
            'movementPoints' => 5,
            'maxMovementPoints' => 5,
            'color' => 0x00FF00
        ];

        $this->assertEquals($expected, $player->toArray());
    }

    public function testFromArrayCreatesCorrectPlayer(): void
    {
        $data = [
            'id' => 'player_456',
            'name' => 'Restored Player',
            'position' => ['row' => 20, 'col' => 25],
            'movementPoints' => 2,
            'maxMovementPoints' => 4,
            'color' => 0x0000FF
        ];

        $player = Player::fromArray($data);

        $this->assertEquals('player_456', $player->getId()->value);
        $this->assertEquals('Restored Player', $player->getName());
        $this->assertEquals(20, $player->getPosition()->row);
        $this->assertEquals(25, $player->getPosition()->col);
        $this->assertEquals(2, $player->currentMovementPoints);
        $this->assertEquals(4, $player->maxMovementPoints);
        $this->assertEquals(0x0000FF, $player->getColor());
    }

    public function testFromArrayWithDefaultValues(): void
    {
        $data = [
            'id' => 'player_789',
            'name' => 'Default Player',
            'position' => ['row' => 5, 'col' => 5]
        ];

        $player = Player::fromArray($data);

        $this->assertEquals(3, $player->maxMovementPoints);
        $this->assertEquals(0xFF6B6B, $player->getColor());
    }

    public function testMovementPointsValueObjectIsAccessible(): void
    {
        $movementPoints = MovementPoints::createFull(5);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);

        // Test that we can access movement points through virtual properties and getter
        $this->assertEquals(5, $player->currentMovementPoints);
        $this->assertEquals(5, $player->maxMovementPoints);
        $this->assertInstanceOf(MovementPoints::class, $player->getMovementPoints());
    }

    public function testDomainEventsTraitFunctionality(): void
    {
        $movementPoints = MovementPoints::createFull(3);
        $player = new Player($this->playerId, $this->position, 'Test Player', $movementPoints);

        // Test initial state
        $this->assertFalse($player->hasDomainEvents());
        $this->assertEquals(0, $player->getDomainEventsCount());

        // Generate an event
        $player->moveTo(new Position(11, 15), 1);

        // Test after event generation
        $this->assertTrue($player->hasDomainEvents());
        $this->assertEquals(1, $player->getDomainEventsCount());

        // Test pullDomainEvents
        $events = $player->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertFalse($player->hasDomainEvents()); // Should be cleared after pull
        $this->assertEquals(0, $player->getDomainEventsCount());
    }
} 