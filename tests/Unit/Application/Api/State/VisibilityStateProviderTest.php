<?php

namespace App\Tests\Unit\Application\Api\State;

use App\Application\Api\State\VisibilityStateProvider;
use App\Application\Visibility\Query\GetGameVisibilityQuery;
use App\Application\Visibility\Query\GetPlayerVisibilityQuery;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\UI\Api\Resource\VisibilityResource;
use ApiPlatform\Metadata\Operation;
use Ecotone\Modelling\QueryBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class VisibilityStateProviderTest extends TestCase
{
    private VisibilityStateProvider $provider;
    private QueryBus $queryBus;
    private ObjectMapperInterface $objectMapper;

    protected function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBus::class);
        $this->objectMapper = $this->createMock(ObjectMapperInterface::class);
        $this->provider = new VisibilityStateProvider($this->queryBus, $this->objectMapper);
    }

    public function testProvidePlayerVisibility(): void
    {
        $operation = $this->createMock(Operation::class);
        $operation->method('getName')->willReturn('get_player_visibility');
        
        $uriVariables = ['playerId' => '123e4567-e89b-12d3-a456-426614174001'];
        
        $visibilityData = [
            (object) ['playerId' => '123e4567-e89b-12d3-a456-426614174001', 'gameId' => '123e4567-e89b-12d3-a456-426614174002', 'x' => 5, 'y' => 5, 'state' => 'active', 'updatedAt' => '2024-01-01T00:00:00Z']
        ];
        
        $expectedResources = [
            new VisibilityResource('123e4567-e89b-12d3-a456-426614174001', '123e4567-e89b-12d3-a456-426614174002', 5, 5, 'active', '2024-01-01T00:00:00Z')
        ];

        $this->queryBus->expects($this->once())
            ->method('send')
            ->with($this->callback(function (GetPlayerVisibilityQuery $query) {
                return (string)$query->playerId === '123e4567-e89b-12d3-a456-426614174001';
            }))
            ->willReturn($visibilityData);

        $this->objectMapper->expects($this->once())
            ->method('map')
            ->with($visibilityData[0], VisibilityResource::class)
            ->willReturn($expectedResources[0]);

        $result = $this->provider->provide($operation, $uriVariables);

        $this->assertEquals($expectedResources, $result);
    }

    public function testProvideGameVisibility(): void
    {
        $operation = $this->createMock(Operation::class);
        $operation->method('getName')->willReturn('get_game_visibility');
        
        $uriVariables = ['gameId' => '123e4567-e89b-12d3-a456-426614174002'];
        
        $visibilityData = [
            (object) ['playerId' => '123e4567-e89b-12d3-a456-426614174001', 'gameId' => '123e4567-e89b-12d3-a456-426614174002', 'x' => 5, 'y' => 5, 'state' => 'active', 'updatedAt' => '2024-01-01T00:00:00Z'],
            (object) ['playerId' => '123e4567-e89b-12d3-a456-426614174003', 'gameId' => '123e4567-e89b-12d3-a456-426614174002', 'x' => 6, 'y' => 6, 'state' => 'discovered', 'updatedAt' => '2024-01-01T00:00:00Z']
        ];
        
        $expectedResources = [
            new VisibilityResource('123e4567-e89b-12d3-a456-426614174001', '123e4567-e89b-12d3-a456-426614174002', 5, 5, 'active', '2024-01-01T00:00:00Z'),
            new VisibilityResource('123e4567-e89b-12d3-a456-426614174003', '123e4567-e89b-12d3-a456-426614174002', 6, 6, 'discovered', '2024-01-01T00:00:00Z')
        ];

        $this->queryBus->expects($this->once())
            ->method('send')
            ->with($this->callback(function (GetGameVisibilityQuery $query) {
                return (string)$query->gameId === '123e4567-e89b-12d3-a456-426614174002';
            }))
            ->willReturn($visibilityData);

        $callCount = 0;
        $this->objectMapper->expects($this->exactly(2))
            ->method('map')
            ->willReturnCallback(function ($entity, $class) use ($expectedResources, &$callCount) {
                $this->assertEquals(VisibilityResource::class, $class);
                return $expectedResources[$callCount++];
            });

        $result = $this->provider->provide($operation, $uriVariables);

        $this->assertEquals($expectedResources, $result);
    }

    public function testProvideWithUnknownOperation(): void
    {
        $operation = $this->createMock(Operation::class);
        $operation->method('getName')->willReturn('unknown_operation');
        
        $uriVariables = [];

        $this->queryBus->expects($this->never())
            ->method('send');

        $this->objectMapper->expects($this->never())
            ->method('map');

        $result = $this->provider->provide($operation, $uriVariables);

        $this->assertNull($result);
    }

    public function testProvidePlayerVisibilityWithEmptyResult(): void
    {
        $operation = $this->createMock(Operation::class);
        $operation->method('getName')->willReturn('get_player_visibility');
        
        $uriVariables = ['playerId' => '123e4567-e89b-12d3-a456-426614174001'];

        $this->queryBus->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $this->objectMapper->expects($this->never())
            ->method('map');

        $result = $this->provider->provide($operation, $uriVariables);

        $this->assertEquals([], $result);
    }

    public function testProvideGameVisibilityWithEmptyResult(): void
    {
        $operation = $this->createMock(Operation::class);
        $operation->method('getName')->willReturn('get_game_visibility');
        
        $uriVariables = ['gameId' => '123e4567-e89b-12d3-a456-426614174002'];

        $this->queryBus->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $this->objectMapper->expects($this->never())
            ->method('map');

        $result = $this->provider->provide($operation, $uriVariables);

        $this->assertEquals([], $result);
    }
} 