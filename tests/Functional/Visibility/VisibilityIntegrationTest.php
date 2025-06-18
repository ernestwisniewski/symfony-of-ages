<?php

namespace App\Tests\Functional\Visibility;

use App\Application\Visibility\Command\UpdateVisibilityCommand;
use App\Application\Visibility\Query\GetGameVisibilityQuery;
use App\Application\Visibility\Query\GetPlayerVisibilityQuery;
use App\Application\Visibility\Service\VisibilityApplicationService;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitType;
use App\Infrastructure\Visibility\ReadModel\Doctrine\PlayerVisibilityEntity;
use App\Infrastructure\Visibility\ReadModel\Doctrine\PlayerVisibilityRepository;
use App\Tests\Functional\Api\BaseFunctionalTestCase;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class VisibilityIntegrationTest extends BaseFunctionalTestCase
{
    private VisibilityApplicationService $visibilityService;
    private PlayerVisibilityRepository $visibilityRepository;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->visibilityService = $this->getContainer()->get(VisibilityApplicationService::class);
        $this->visibilityRepository = $this->getContainer()->get(PlayerVisibilityRepository::class);
        $this->entityManager = $this->getContainer()->get(EntityManagerInterface::class);
    }

    public function testUpdatePlayerVisibilityWithUnit(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $units = [
            (object) [
                'position' => new \App\Domain\Shared\ValueObject\Position(5, 5),
                'type' => UnitType::SCOUT->value
            ]
        ];
        
        $cities = [];

        $this->visibilityService->updatePlayerVisibility($playerId, $units, $cities);

        $visibilityEntities = $this->visibilityRepository->findByPlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $this->assertGreaterThan(0, count($visibilityEntities));
        
        $activeHexes = array_filter($visibilityEntities, fn($entity) => $entity->state === 'active');
        $this->assertGreaterThan(0, count($activeHexes));
    }

    public function testUpdatePlayerVisibilityWithCity(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $units = [];
        
        $cities = [
            (object) [
                'position' => new \App\Domain\Shared\ValueObject\Position(5, 5),
                'level' => 2
            ]
        ];

        $this->visibilityService->updatePlayerVisibility($playerId, $units, $cities);

        $visibilityEntities = $this->visibilityRepository->findByPlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $this->assertGreaterThan(0, count($visibilityEntities));
        
        $activeHexes = array_filter($visibilityEntities, fn($entity) => $entity->state === 'active');
        $this->assertGreaterThan(0, count($activeHexes));
    }

    public function testUpdatePlayerVisibilityWithMultipleSources(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $units = [
            (object) [
                'position' => new \App\Domain\Shared\ValueObject\Position(5, 5),
                'type' => UnitType::WARRIOR->value
            ]
        ];
        
        $cities = [
            (object) [
                'position' => new \App\Domain\Shared\ValueObject\Position(6, 6),
                'level' => 1
            ]
        ];

        $this->visibilityService->updatePlayerVisibility($playerId, $units, $cities);

        $visibilityEntities = $this->visibilityRepository->findByPlayerId('123e4567-e89b-12d3-a456-426614174001');
        
        $this->assertGreaterThan(0, count($visibilityEntities));
    }

    public function testGetPlayerVisibility(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174002');

        $entity = new PlayerVisibilityEntity(
            '123e4567-e89b-12d3-a456-426614174001',
            '123e4567-e89b-12d3-a456-426614174002',
            5,
            5,
            'active',
            new DateTimeImmutable('2024-01-01T00:00:00Z')
        );
        
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $visibility = $this->visibilityService->getPlayerVisibility($playerId, $gameId);
        
        $this->assertCount(1, $visibility);
        $this->assertEquals(5, $visibility[0]->x);
        $this->assertEquals(5, $visibility[0]->y);
        $this->assertEquals('active', $visibility[0]->state);
    }

    public function testGetGameVisibility(): void
    {
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174002');

        $entity1 = new PlayerVisibilityEntity(
            '123e4567-e89b-12d3-a456-426614174001',
            '123e4567-e89b-12d3-a456-426614174002',
            5,
            5,
            'active',
            new DateTimeImmutable('2024-01-01T00:00:00Z')
        );
        
        $entity2 = new PlayerVisibilityEntity(
            '123e4567-e89b-12d3-a456-426614174003',
            '123e4567-e89b-12d3-a456-426614174002',
            6,
            6,
            'discovered',
            new DateTimeImmutable('2024-01-01T00:00:00Z')
        );
        
        $this->entityManager->persist($entity1);
        $this->entityManager->persist($entity2);
        $this->entityManager->flush();

        $visibility = $this->visibilityService->getGameVisibility($gameId);
        
        $this->assertCount(2, $visibility);
    }

    public function testIsHexVisibleForPlayer(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174002');

        $entity = new PlayerVisibilityEntity(
            '123e4567-e89b-12d3-a456-426614174001',
            '123e4567-e89b-12d3-a456-426614174002',
            5,
            5,
            'active',
            new DateTimeImmutable('2024-01-01T00:00:00Z')
        );
        
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->assertTrue($this->visibilityService->isHexVisibleForPlayer(5, 5, $playerId, $gameId));
        $this->assertFalse($this->visibilityService->isHexVisibleForPlayer(6, 6, $playerId, $gameId));
    }

    public function testIsHexDiscoveredForPlayer(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174002');

        $entity = new PlayerVisibilityEntity(
            '123e4567-e89b-12d3-a456-426614174001',
            '123e4567-e89b-12d3-a456-426614174002',
            5,
            5,
            'discovered',
            new DateTimeImmutable('2024-01-01T00:00:00Z')
        );
        
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $this->assertTrue($this->visibilityService->isHexDiscoveredForPlayer(5, 5, $playerId, $gameId));
        $this->assertFalse($this->visibilityService->isHexDiscoveredForPlayer(6, 6, $playerId, $gameId));
    }

    public function testVisibilityCalculatorIntegration(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174002');
        
        $units = [
            (object) [
                'position' => ['x' => 5, 'y' => 5],
                'type' => UnitType::SCOUT->value
            ]
        ];
        
        $cities = [];

        $this->visibilityService->updatePlayerVisibility($playerId, $gameId, $units, $cities);

        $visibilityEntities = $this->visibilityRepository->findByPlayerAndGame('123e4567-e89b-12d3-a456-426614174001', '123e4567-e89b-12d3-a456-426614174002');
        
        $this->assertGreaterThanOrEqual(1, count($visibilityEntities));
        
        $activeHexes = array_filter($visibilityEntities, fn($entity) => $entity->state === 'active');
        $this->assertGreaterThanOrEqual(1, count($activeHexes));
    }

    public function testVisibilityStateTransitions(): void
    {
        $playerId = new PlayerId('123e4567-e89b-12d3-a456-426614174001');
        $gameId = new GameId('123e4567-e89b-12d3-a456-426614174002');
        
        $units = [
            (object) [
                'position' => ['x' => 5, 'y' => 5],
                'type' => UnitType::WARRIOR->value
            ]
        ];
        
        $cities = [];

        $this->visibilityService->updatePlayerVisibility($playerId, $gameId, $units, $cities);

        $visibilityEntities = $this->visibilityRepository->findByPlayerAndGame('123e4567-e89b-12d3-a456-426614174001', '123e4567-e89b-12d3-a456-426614174002');
        
        $this->assertGreaterThan(0, count($visibilityEntities));
        
        $activeHexes = array_filter($visibilityEntities, fn($entity) => $entity->state === 'active');
        $discoveredHexes = array_filter($visibilityEntities, fn($entity) => $entity->state === 'discovered');
        
        $this->assertGreaterThan(0, count($activeHexes));
        $this->assertGreaterThanOrEqual(0, count($discoveredHexes));
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM App\Infrastructure\Visibility\ReadModel\Doctrine\PlayerVisibilityEntity')->execute();
        parent::tearDown();
    }
} 