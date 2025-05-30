<?php

namespace Tests\Unit\Domain\Player\Factory;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Factory\PlayerFactory;
use App\Domain\Player\Service\PlayerAttributeDomainService;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PlayerFactory after encapsulation improvements
 */
class PlayerFactoryTest extends TestCase
{
    private PlayerFactory $playerFactory;
    private PlayerAttributeDomainService|MockObject $attributeService;

    protected function setUp(): void
    {
        $this->attributeService = $this->createMock(PlayerAttributeDomainService::class);
        $this->playerFactory = new PlayerFactory($this->attributeService);
    }

    public function testCreatePlayerWithBasicParameters(): void
    {
        $name = 'Test Player';
        $position = new Position(10, 15);
        $maxMovementPoints = 5;

        // Mock attribute service for color generation only
        $expectedColor = 0xFF6B6B;

        $this->attributeService
            ->expects($this->once())
            ->method('generatePlayerColor')
            ->willReturn($expectedColor);

        $player = $this->playerFactory->createPlayer($name, $position, $maxMovementPoints);

        $this->assertInstanceOf(Player::class, $player);
        $this->assertInstanceOf(PlayerId::class, $player->getId()); // ID is generated internally
        $this->assertEquals($name, $player->getName());
        $this->assertEquals($position, $player->getPosition());
        $this->assertEquals($maxMovementPoints, $player->maxMovementPoints);
        $this->assertEquals($expectedColor, $player->getColor());
    }

    public function testCreatePlayerWithDefaultMovementPoints(): void
    {
        $name = 'Default Player';
        $position = new Position(5, 5);

        $this->attributeService
            ->method('generatePlayerColor')
            ->willReturn(0x00FF00);

        $player = $this->playerFactory->createPlayer($name, $position);

        $this->assertEquals(3, $player->maxMovementPoints); // Default value
    }

    public function testCreatePlayerWithAttributes(): void
    {
        $id = new PlayerId('custom_player_789');
        $name = 'Custom Player';
        $position = new Position(20, 25);
        $maxMovementPoints = 4;
        $color = 0x0000FF;

        $player = $this->playerFactory->createPlayerWithAttributes(
            $id,
            $name,
            $position,
            $maxMovementPoints,
            $color
        );

        $this->assertInstanceOf(Player::class, $player);
        $this->assertEquals($id, $player->getId());
        $this->assertEquals($name, $player->getName());
        $this->assertEquals($position, $player->getPosition());
        $this->assertEquals($maxMovementPoints, $player->maxMovementPoints);
        $this->assertEquals($color, $player->getColor());
    }

    public function testCreatePlayerWithAttributesDefaults(): void
    {
        $id = new PlayerId('default_player');
        $name = 'Default Attributes Player';
        $position = new Position(1, 1);

        $player = $this->playerFactory->createPlayerWithAttributes($id, $name, $position);

        $this->assertEquals(3, $player->maxMovementPoints);
        $this->assertEquals(0xFF6B6B, $player->getColor());
    }

    public function testCreateTestPlayer(): void
    {
        $this->attributeService
            ->expects($this->once())
            ->method('generatePlayerColor')
            ->willReturn(0xFF0000);

        $player = $this->playerFactory->createTestPlayer();

        $this->assertInstanceOf(Player::class, $player);
        $this->assertEquals('Test Player', $player->getName());
        $this->assertEquals(50, $player->getPosition()->row); // Updated to match actual implementation
        $this->assertEquals(50, $player->getPosition()->col);
    }

    public function testCreateTestPlayerWithCustomName(): void
    {
        $customName = 'Custom Test Player';

        $this->attributeService
            ->method('generatePlayerColor')
            ->willReturn(0x00FF00);

        $player = $this->playerFactory->createTestPlayer($customName);

        $this->assertEquals($customName, $player->getName());
    }

    public function testCreateTestPlayerWithCustomPosition(): void
    {
        $customPosition = new Position(15, 20);

        $this->attributeService
            ->method('generatePlayerColor')
            ->willReturn(0x0000FF);

        $player = $this->playerFactory->createTestPlayer('Positioned Player', $customPosition);

        $this->assertEquals($customPosition, $player->getPosition());
    }

    public function testGetAvailableColors(): void
    {
        $expectedColors = [
            0xFF6B6B, // Red
            0x4ECDC4, // Teal
            0x45B7D1, // Blue
            0x96CEB4, // Green
            0xFEDCA4, // Orange
            0xD4A5A5  // Pink
        ];

        $this->attributeService
            ->expects($this->once())
            ->method('getAvailableColors')
            ->willReturn($expectedColors);

        $colors = $this->playerFactory->getAvailableColors();

        $this->assertEquals($expectedColors, $colors);
    }

    public function testFactoryGeneratesUniqueIds(): void
    {
        $position = new Position(0, 0);

        // Mock color generation only - IDs are generated via PlayerId::generate()
        $this->attributeService
            ->method('generatePlayerColor')
            ->willReturn(0xFF6B6B);

        $player1 = $this->playerFactory->createPlayer('Player 1', $position);
        $player2 = $this->playerFactory->createPlayer('Player 2', $position);
        $player3 = $this->playerFactory->createPlayer('Player 3', $position);

        $this->assertNotEquals($player1->getId()->value, $player2->getId()->value);
        $this->assertNotEquals($player2->getId()->value, $player3->getId()->value);
        $this->assertNotEquals($player1->getId()->value, $player3->getId()->value);
    }

    public function testFactoryGeneratesVariousColors(): void
    {
        $position = new Position(0, 0);

        // Mock different colors for each call
        $this->attributeService
            ->expects($this->exactly(3))
            ->method('generatePlayerColor')
            ->willReturnOnConsecutiveCalls(0xFF0000, 0x00FF00, 0x0000FF);

        $player1 = $this->playerFactory->createPlayer('Red Player', $position);
        $player2 = $this->playerFactory->createPlayer('Green Player', $position);
        $player3 = $this->playerFactory->createPlayer('Blue Player', $position);

        $this->assertEquals(0xFF0000, $player1->getColor());
        $this->assertEquals(0x00FF00, $player2->getColor());
        $this->assertEquals(0x0000FF, $player3->getColor());
    }

    public function testCreatedPlayerHasCorrectInitialState(): void
    {
        $this->attributeService
            ->method('generatePlayerColor')
            ->willReturn(0xFF6B6B);

        $player = $this->playerFactory->createPlayer('State Test', new Position(0, 0), 4);

        // Test initial state
        $this->assertEquals(4, $player->currentMovementPoints); // Should equal max initially
        $this->assertEquals(4, $player->maxMovementPoints);
        $this->assertTrue($player->canContinueTurn());
        $this->assertEmpty($player->getDomainEvents());
    }
} 