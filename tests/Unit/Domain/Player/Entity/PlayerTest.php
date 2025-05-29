<?php

namespace App\Tests\Unit\Domain\Player\Entity;

use App\Domain\Game\ValueObject\PlayerId;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Event\PlayerMoved;
use App\Domain\Player\ValueObject\Position;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Player entity
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
        $player = new Player(
            $this->playerId,
            $this->position,
            'Test Player',
            3,
            0xFF6B6B
        );

        $this->assertEquals($this->playerId, $player->getId());
        $this->assertEquals($this->position, $player->getPosition());
        $this->assertEquals('Test Player', $player->getName());
        $this->assertEquals(3, $player->getMovementPoints());
        $this->assertEquals(3, $player->getMaxMovementPoints());
        $this->assertEquals(0xFF6B6B, $player->getColor());
    }

    public function testCanCreatePlayerWithDefaultValues(): void
    {
        $player = new Player($this->playerId, $this->position, 'Default Player');

        $this->assertEquals(3, $player->getMaxMovementPoints());
        $this->assertEquals(0xFF6B6B, $player->getColor());
    }

    public function testThrowsExceptionForEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Player name cannot be empty');

        new Player($this->playerId, $this->position, '');
    }

    public function testThrowsExceptionForWhitespaceOnlyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Player name cannot be empty');

        new Player($this->playerId, $this->position, '   ');
    }

    public function testThrowsExceptionForTooLongName(): void
    {
        $longName = str_repeat('a', 51);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Player name cannot exceed 50 characters');

        new Player($this->playerId, $this->position, $longName);
    }

    public function testCanMoveToValidPosition(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');
        $newPosition = new Position(11, 15);

        $result = $player->moveTo($newPosition, 2);

        $this->assertTrue($result);
        $this->assertEquals($newPosition, $player->getPosition());
        $this->assertEquals(1, $player->getMovementPoints()); // 3 - 2 = 1
    }

    public function testCannotMoveWithInsufficientMovementPoints(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');
        $newPosition = new Position(11, 15);

        $result = $player->moveTo($newPosition, 5); // Need 5, have 3

        $this->assertFalse($result);
        $this->assertEquals($this->position, $player->getPosition()); // Position unchanged
        $this->assertEquals(3, $player->getMovementPoints()); // Movement points unchanged
    }

    public function testMoveToPublishesDomainEvent(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');
        $newPosition = new Position(11, 15);

        $player->moveTo($newPosition, 2);

        $events = $player->getDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PlayerMoved::class, $events[0]);

        $event = $events[0];
        $this->assertEquals($this->playerId, $event->getPlayerId());
        $this->assertEquals($this->position, $event->getFromPosition());
        $this->assertEquals($newPosition, $event->getToPosition());
        $this->assertEquals(2, $event->getMovementCost());
    }

    public function testCanMoveToReturnsTrueForSufficientPoints(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');

        $this->assertTrue($player->canMoveTo(2));
        $this->assertTrue($player->canMoveTo(3));
    }

    public function testCanMoveToReturnsFalseForInsufficientPoints(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');

        $this->assertFalse($player->canMoveTo(4));
        $this->assertFalse($player->canMoveTo(10));
    }

    public function testStartNewTurnRestoresMovementPoints(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');
        
        // Spend some movement points
        $player->moveTo(new Position(11, 15), 2);
        $this->assertEquals(1, $player->getMovementPoints());

        // Start new turn
        $player->startNewTurn();
        $this->assertEquals(3, $player->getMovementPoints());
    }

    public function testCanContinueTurnReturnsTrueWithMovementPoints(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');

        $this->assertTrue($player->canContinueTurn());
    }

    public function testCanContinueTurnReturnsFalseWithoutMovementPoints(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');
        
        // Spend all movement points
        $player->moveTo(new Position(11, 15), 3);

        $this->assertFalse($player->canContinueTurn());
    }

    public function testCanChangeNameWithValidName(): void
    {
        $player = new Player($this->playerId, $this->position, 'Old Name');

        $player->changeName('New Name');

        $this->assertEquals('New Name', $player->getName());
    }

    public function testCannotChangeNameToEmptyString(): void
    {
        $player = new Player($this->playerId, $this->position, 'Valid Name');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Player name cannot be empty');

        $player->changeName('');
    }

    public function testCanChangeColorWithValidColor(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');

        $player->changeColor(0x00FF00);

        $this->assertEquals(0x00FF00, $player->getColor());
    }

    public function testCannotChangeColorToNegativeValue(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Color must be a valid hexadecimal value');

        $player->changeColor(-1);
    }

    public function testCannotChangeColorToValueAboveMaximum(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Color must be a valid hexadecimal value');

        $player->changeColor(0x1000000); // Greater than 0xFFFFFF
    }

    public function testClearDomainEventsRemovesAllEvents(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player');
        
        // Generate some events
        $player->moveTo(new Position(11, 15), 1);
        $player->moveTo(new Position(12, 15), 1);

        $this->assertCount(2, $player->getDomainEvents());

        $player->clearDomainEvents();

        $this->assertCount(0, $player->getDomainEvents());
    }

    public function testToArrayReturnsCorrectData(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player', 5, 0x00FF00);

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

        $this->assertEquals('player_456', $player->getId()->getValue());
        $this->assertEquals('Restored Player', $player->getName());
        $this->assertEquals(20, $player->getPosition()->getRow());
        $this->assertEquals(25, $player->getPosition()->getCol());
        $this->assertEquals(2, $player->getMovementPoints());
        $this->assertEquals(4, $player->getMaxMovementPoints());
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

        $this->assertEquals(3, $player->getMaxMovementPoints());
        $this->assertEquals(0xFF6B6B, $player->getColor());
    }

    public function testMovementPointsValueObjectIsAccessible(): void
    {
        $player = new Player($this->playerId, $this->position, 'Test Player', 5);

        $movementPoints = $player->getMovementPointsValueObject();

        $this->assertEquals(5, $movementPoints->getCurrent());
        $this->assertEquals(5, $movementPoints->getMaximum());
    }
} 