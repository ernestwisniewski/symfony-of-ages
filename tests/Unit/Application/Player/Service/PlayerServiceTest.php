<?php

namespace Tests\Unit\Application\Player\Service;

use App\Application\Player\Service\PlayerService;
use App\Application\Player\Service\PlayerCreationService;
use App\Application\Player\Service\PlayerMovementService;
use App\Application\Player\Service\PlayerTurnService;
use App\Application\Player\Service\PlayerPositionService;
use App\Application\Player\Service\PlayerAttributeService;
use App\Application\Player\Service\MovementCalculationService;
use App\Application\Map\Service\HexNeighborService;
use App\Domain\Shared\Service\HexGridService;
use App\Domain\Player\Service\PlayerTurnDomainService;
use App\Domain\Player\Service\PlayerAttributeDomainService;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Player\ValueObject\Position;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for PlayerService
 */
class PlayerServiceTest extends TestCase
{
    private PlayerService $playerService;
    private PlayerCreationService|MockObject $creationService;
    private PlayerMovementService|MockObject $movementService;
    private PlayerTurnService|MockObject $turnService;
    private PlayerPositionService|MockObject $positionService;
    private PlayerAttributeService|MockObject $attributeService;
    private MovementCalculationService|MockObject $movementCalculationService;
    private HexNeighborService|MockObject $hexNeighborService;
    private HexGridService|MockObject $hexGridService;
    private PlayerTurnDomainService|MockObject $turnDomainService;
    private PlayerAttributeDomainService|MockObject $attributeDomainService;

    protected function setUp(): void
    {
        $this->creationService = $this->createMock(PlayerCreationService::class);
        $this->movementService = $this->createMock(PlayerMovementService::class);
        $this->turnService = $this->createMock(PlayerTurnService::class);
        $this->positionService = $this->createMock(PlayerPositionService::class);
        $this->attributeService = $this->createMock(PlayerAttributeService::class);
        $this->movementCalculationService = $this->createMock(MovementCalculationService::class);
        $this->hexNeighborService = $this->createMock(HexNeighborService::class);
        $this->hexGridService = $this->createMock(HexGridService::class);
        $this->turnDomainService = $this->createMock(PlayerTurnDomainService::class);
        $this->attributeDomainService = $this->createMock(PlayerAttributeDomainService::class);

        $this->playerService = new PlayerService(
            $this->creationService,
            $this->movementService,
            $this->turnService,
            $this->positionService,
            $this->attributeService,
            $this->movementCalculationService,
            $this->hexGridService,
            $this->turnDomainService,
            $this->attributeDomainService,
            $this->hexNeighborService
        );
    }

    public function testCreatePlayer(): void
    {
        $name = 'Test Player';
        $mapRows = 60;
        $mapCols = 80;
        $mapData = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $expectedPosition = new Position(30, 40);

        $expectedPlayer = Player::create(
            new PlayerId('player_123'),
            $expectedPosition,
            $name,
            3
        );

        $this->creationService->expects($this->once())
            ->method('createPlayer')
            ->with($name, $mapRows, $mapCols, $mapData, 3)
            ->willReturn($expectedPlayer);

        $result = $this->playerService->createPlayer($name, $mapRows, $mapCols, $mapData);

        $this->assertSame($expectedPlayer, $result);
    }

    public function testCreateTestPlayer(): void
    {
        $name = 'Test Player';
        $position = new Position(10, 10);

        $expectedPlayer = Player::create(
            new PlayerId('test_player'),
            $position,
            $name,
            3
        );

        $this->creationService->expects($this->once())
            ->method('createTestPlayer')
            ->with($name, $position)
            ->willReturn($expectedPlayer);

        $result = $this->playerService->createTestPlayer($name, $position);

        $this->assertSame($expectedPlayer, $result);
    }

    public function testMovePlayer(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $targetPosition = new Position(5, 6);
        $mapData = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $expectedResult = [
            'success' => true,
            'message' => 'Player moved successfully',
            'remainingMovement' => 2
        ];

        $this->movementService->expects($this->once())
            ->method('movePlayer')
            ->with($player, $targetPosition, $mapData)
            ->willReturn($expectedResult);

        $result = $this->playerService->movePlayer($player, $targetPosition, $mapData);

        $this->assertEquals($expectedResult, $result);
    }

    public function testStartPlayerTurn(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $this->turnDomainService->expects($this->once())
            ->method('startNewTurn')
            ->with($player);

        $this->playerService->startPlayerTurn($player);
    }

    public function testEndPlayerTurn(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $this->turnService->expects($this->once())
            ->method('endPlayerTurn')
            ->with($player);

        $this->playerService->endPlayerTurn($player);
    }

    public function testCanPlayerMoveToPosition(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $movementCost = 2;

        $this->movementService->expects($this->once())
            ->method('canPlayerMoveToPosition')
            ->with($player, $movementCost)
            ->willReturn(true);

        $result = $this->playerService->canPlayerMoveToPosition($player, $movementCost);

        $this->assertTrue($result);
    }

    public function testCanPlayerContinueTurn(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $this->turnDomainService->expects($this->once())
            ->method('canPlayerContinueTurn')
            ->with($player)
            ->willReturn(true);

        $result = $this->playerService->canPlayerContinueTurn($player);

        $this->assertTrue($result);
    }

    public function testArePositionsAdjacent(): void
    {
        $from = new Position(5, 5);
        $to = new Position(5, 6);

        $this->movementService->expects($this->once())
            ->method('arePositionsAdjacent')
            ->with($from, $to)
            ->willReturn(true);

        $result = $this->playerService->arePositionsAdjacent($from, $to);

        $this->assertTrue($result);
    }

    public function testGetTerrainMovementCost(): void
    {
        $terrainData = ['type' => 'forest', 'properties' => ['movementCost' => 2]];

        $this->movementService->expects($this->once())
            ->method('getTerrainMovementCost')
            ->with($terrainData)
            ->willReturn(2);

        $result = $this->playerService->getTerrainMovementCost($terrainData);

        $this->assertEquals(2, $result);
    }

    public function testGetAvailablePlayerColors(): void
    {
        $expectedColors = [0xFF6B6B, 0x4ECDC4, 0x45B7D1];

        $this->attributeDomainService->expects($this->once())
            ->method('getAvailableColors')
            ->willReturn($expectedColors);

        $result = $this->playerService->getAvailablePlayerColors();

        $this->assertEquals($expectedColors, $result);
    }

    public function testGetPlayerStatus(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $this->attributeDomainService->expects($this->once())
            ->method('getColorName')
            ->with($player->getColor())
            ->willReturn('Red');

        $this->turnDomainService->expects($this->once())
            ->method('calculateMovementEfficiency')
            ->with($player)
            ->willReturn(0.0);

        $this->turnDomainService->expects($this->once())
            ->method('canPlayerContinueTurn')
            ->with($player)
            ->willReturn(true);

        $this->turnDomainService->expects($this->once())
            ->method('shouldEndTurn')
            ->with($player)
            ->willReturn(false);

        $result = $this->playerService->getPlayerStatus($player);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('basic_info', $result);
        $this->assertArrayHasKey('position', $result);
        $this->assertArrayHasKey('movement', $result);
        $this->assertArrayHasKey('turn_status', $result);
    }

    public function testCalculatePlayerPossibleMoves(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $mapData = [
            [['type' => 'plains', 'name' => 'Plains']]
        ];

        $expectedMoves = [
            ['row' => 5, 'col' => 6, 'cost' => 1],
            ['row' => 6, 'col' => 5, 'cost' => 2]
        ];

        $this->movementCalculationService->expects($this->once())
            ->method('calculatePossibleMoves')
            ->with($player, $mapData, 60, 80)
            ->willReturn($expectedMoves);

        $result = $this->playerService->calculatePlayerPossibleMoves($player, $mapData, 60, 80);

        $this->assertEquals($expectedMoves, $result);
    }

    public function testValidatePlayerPosition(): void
    {
        $position = new Position(0, 5);
        $mapData = [
            [
                0 => [],
                1 => [],
                2 => [],
                3 => [],
                4 => [],
                5 => ['type' => 'plains', 'name' => 'Plains', 'properties' => ['movementCost' => 1]]
            ]
        ];

        $this->positionService->expects($this->once())
            ->method('isValidMapPosition')
            ->with($position, 60, 80)
            ->willReturn(true);

        $this->positionService->expects($this->once())
            ->method('isValidStartingPosition')
            ->with($position, $mapData)
            ->willReturn(true);

        $this->movementService->expects($this->once())
            ->method('getTerrainMovementCost')
            ->willReturn(1);

        $result = $this->playerService->validatePlayerPosition($position, $mapData, 60, 80);

        $this->assertIsArray($result);
        $this->assertTrue($result['valid']);
        $this->assertEquals('Position is suitable', $result['reason']);
        $this->assertEquals('VALID', $result['code']);
    }

    public function testAnalyzePlayerTacticalSituation(): void
    {
        $player = Player::create(
            new PlayerId('player_123'),
            new Position(5, 5),
            'Test Player',
            3
        );

        $mapData = [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [],
            5 => [
                0 => [],
                1 => [],
                2 => [],
                3 => [],
                4 => [],
                5 => ['type' => 'plains', 'name' => 'Plains'],
                6 => ['type' => 'plains', 'name' => 'Plains']
            ]
        ];

        // Mock HexNeighborService calls for map operations
        $this->hexNeighborService->expects($this->once())
            ->method('getNeighborTiles')
            ->willReturn([
                ['type' => 'plains', 'name' => 'Plains', 'coordinates' => ['row' => 5, 'col' => 6]]
            ]);

        // Mock HexGridService calls for position calculations
        $this->hexGridService->expects($this->once())
            ->method('getAdjacentPositions')
            ->willReturn([
                new Position(5, 6)
            ]);

        // Mock domain service calls
        $this->turnDomainService->expects($this->once())
            ->method('calculateMovementEfficiency')
            ->with($player)
            ->willReturn(0.5);

        $result = $this->playerService->analyzePlayerTacticalSituation($player, $mapData, 60, 80);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('current_position', $result);
        $this->assertArrayHasKey('current_terrain', $result);
        $this->assertArrayHasKey('movement_points', $result);
        $this->assertArrayHasKey('surrounding_terrain', $result);
        $this->assertArrayHasKey('movement_options', $result);
        $this->assertArrayHasKey('tactical_advantages', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('turn_efficiency', $result);
    }
} 