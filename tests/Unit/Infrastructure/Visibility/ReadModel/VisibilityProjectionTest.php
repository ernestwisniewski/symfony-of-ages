<?php

namespace App\Tests\Unit\Infrastructure\Visibility\ReadModel;

use App\Application\Visibility\Query\GetGameVisibilityQuery;
use App\Application\Visibility\Query\GetPlayerVisibilityQuery;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Visibility\Event\VisibilityRevealed;
use App\Domain\Visibility\Event\VisibilityUpdated;
use App\Infrastructure\Visibility\ReadModel\Doctrine\PlayerVisibilityEntity;
use App\Infrastructure\Visibility\ReadModel\Doctrine\PlayerVisibilityRepository;
use App\Infrastructure\Visibility\ReadModel\VisibilityProjection;
use App\UI\Visibility\ViewModel\PlayerVisibilityView;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class VisibilityProjectionTest extends TestCase
{
    private VisibilityProjection $projection;
    private EntityManagerInterface $entityManager;
    private PlayerVisibilityRepository $repository;
    private ObjectMapperInterface $objectMapper;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(PlayerVisibilityRepository::class);
        $this->objectMapper = $this->createMock(ObjectMapperInterface::class);
        $this->projection = new VisibilityProjection($this->entityManager, $this->repository, $this->objectMapper);
    }

    public function testGetPlayerVisibility(): void
    {
        $query = new GetPlayerVisibilityQuery(
            new PlayerId('123e4567-e89b-12d3-a456-426614174001'),
            new GameId('123e4567-e89b-12d3-a456-426614174002')
        );

        $entities = [
            new PlayerVisibilityEntity('123e4567-e89b-12d3-a456-426614174001', '123e4567-e89b-12d3-a456-426614174002', 5, 5, 'active', new DateTimeImmutable('2024-01-01T00:00:00Z')),
            new PlayerVisibilityEntity('123e4567-e89b-12d3-a456-426614174001', '123e4567-e89b-12d3-a456-426614174002', 6, 6, 'discovered', new DateTimeImmutable('2024-01-01T00:00:00Z'))
        ];

        $this->repository->expects($this->once())
            ->method('findByPlayerAndGame')
            ->with('123e4567-e89b-12d3-a456-426614174001', '123e4567-e89b-12d3-a456-426614174002')
            ->willReturn($entities);

        // The projection doesn't use object mapper, so no expectations needed
        $this->objectMapper->expects($this->never())
            ->method('map');

        $result = $this->projection->getPlayerVisibility($query);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(PlayerVisibilityView::class, $result[0]);
        $this->assertInstanceOf(PlayerVisibilityView::class, $result[1]);
        
        // Check first entity
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174001', $result[0]->playerId);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174002', $result[0]->gameId);
        $this->assertEquals(5, $result[0]->x);
        $this->assertEquals(5, $result[0]->y);
        $this->assertEquals('active', $result[0]->state);
        $this->assertEquals('2024-01-01T00:00:00Z', $result[0]->updatedAt);
        
        // Check second entity
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174001', $result[1]->playerId);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174002', $result[1]->gameId);
        $this->assertEquals(6, $result[1]->x);
        $this->assertEquals(6, $result[1]->y);
        $this->assertEquals('discovered', $result[1]->state);
        $this->assertEquals('2024-01-01T00:00:00Z', $result[1]->updatedAt);
    }

    public function testGetGameVisibility(): void
    {
        $query = new GetGameVisibilityQuery(new GameId('123e4567-e89b-12d3-a456-426614174002'));

        $entities = [
            new PlayerVisibilityEntity('123e4567-e89b-12d3-a456-426614174001', '123e4567-e89b-12d3-a456-426614174002', 5, 5, 'active', new DateTimeImmutable('2024-01-01T00:00:00Z')),
            new PlayerVisibilityEntity('123e4567-e89b-12d3-a456-426614174003', '123e4567-e89b-12d3-a456-426614174002', 6, 6, 'discovered', new DateTimeImmutable('2024-01-01T00:00:00Z'))
        ];

        $this->repository->expects($this->once())
            ->method('findByGameId')
            ->with('123e4567-e89b-12d3-a456-426614174002')
            ->willReturn($entities);

        // The projection doesn't use object mapper, so no expectations needed
        $this->objectMapper->expects($this->never())
            ->method('map');

        $result = $this->projection->getGameVisibility($query);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(PlayerVisibilityView::class, $result[0]);
        $this->assertInstanceOf(PlayerVisibilityView::class, $result[1]);
        
        // Check first entity
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174001', $result[0]->playerId);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174002', $result[0]->gameId);
        $this->assertEquals(5, $result[0]->x);
        $this->assertEquals(5, $result[0]->y);
        $this->assertEquals('active', $result[0]->state);
        $this->assertEquals('2024-01-01T00:00:00Z', $result[0]->updatedAt);
        
        // Check second entity
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174003', $result[1]->playerId);
        $this->assertEquals('123e4567-e89b-12d3-a456-426614174002', $result[1]->gameId);
        $this->assertEquals(6, $result[1]->x);
        $this->assertEquals(6, $result[1]->y);
        $this->assertEquals('discovered', $result[1]->state);
        $this->assertEquals('2024-01-01T00:00:00Z', $result[1]->updatedAt);
    }

    public function testApplyVisibilityUpdated(): void
    {
        $event = new VisibilityUpdated(
            '123e4567-e89b-12d3-a456-426614174001',
            '123e4567-e89b-12d3-a456-426614174002',
            5,
            5,
            'active',
            '2024-01-01T00:00:00Z'
        );

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (PlayerVisibilityEntity $entity) {
                return $entity->playerId === '123e4567-e89b-12d3-a456-426614174001' &&
                       $entity->gameId === '123e4567-e89b-12d3-a456-426614174002' &&
                       $entity->x === 5 &&
                       $entity->y === 5 &&
                       $entity->state === 'active';
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->projection->applyVisibilityUpdated($event);
    }

    public function testApplyVisibilityRevealed(): void
    {
        $event = new VisibilityRevealed(
            '123e4567-e89b-12d3-a456-426614174001',
            '123e4567-e89b-12d3-a456-426614174002',
            5,
            5,
            '2024-01-01T00:00:00Z'
        );

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (PlayerVisibilityEntity $entity) {
                return $entity->playerId === '123e4567-e89b-12d3-a456-426614174001' &&
                       $entity->gameId === '123e4567-e89b-12d3-a456-426614174002' &&
                       $entity->x === 5 &&
                       $entity->y === 5 &&
                       $entity->state === 'discovered';
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->projection->applyVisibilityRevealed($event);
    }

    public function testGetPlayerVisibilityWithEmptyResult(): void
    {
        $query = new GetPlayerVisibilityQuery(
            new PlayerId('123e4567-e89b-12d3-a456-426614174001'),
            new GameId('123e4567-e89b-12d3-a456-426614174002')
        );

        $this->repository->expects($this->once())
            ->method('findByPlayerAndGame')
            ->with('123e4567-e89b-12d3-a456-426614174001', '123e4567-e89b-12d3-a456-426614174002')
            ->willReturn([]);

        $this->objectMapper->expects($this->never())
            ->method('map');

        $result = $this->projection->getPlayerVisibility($query);

        $this->assertEquals([], $result);
    }

    public function testGetGameVisibilityWithEmptyResult(): void
    {
        $query = new GetGameVisibilityQuery(new GameId('123e4567-e89b-12d3-a456-426614174002'));

        $this->repository->expects($this->once())
            ->method('findByGameId')
            ->with('123e4567-e89b-12d3-a456-426614174002')
            ->willReturn([]);

        $this->objectMapper->expects($this->never())
            ->method('map');

        $result = $this->projection->getGameVisibility($query);

        $this->assertEquals([], $result);
    }
} 