<?php

namespace App\Tests\Unit\Application\Unit\Event\Handler;

use App\Application\City\Query\GetCitiesByPlayerQuery;
use App\Application\Unit\Event\Handler\UpdateVisibilityOnUnitMoveHandler;
use App\Application\Unit\Query\GetUnitsByPlayerQuery;
use App\Application\Visibility\Service\VisibilityApplicationService;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Unit\Event\UnitWasMoved;
use Ecotone\Modelling\QueryBus;
use PHPUnit\Framework\TestCase;

class UpdateVisibilityOnUnitMoveHandlerTest extends TestCase
{
    private QueryBus $queryBus;
    private VisibilityApplicationService $visibilityService;
    private UpdateVisibilityOnUnitMoveHandler $handler;

    protected function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBus::class);
        $this->visibilityService = $this->createMock(VisibilityApplicationService::class);
        $this->handler = new UpdateVisibilityOnUnitMoveHandler(
            $this->queryBus,
            $this->visibilityService
        );
    }

    public function testHandle(): void
    {
        $event = new UnitWasMoved(
            '123e4567-e89b-12d3-a456-426614174001',
            '123e4567-e89b-12d3-a456-426614174002',
            5,
            5,
            6,
            6,
            '2024-01-01T00:00:00Z'
        );

        $units = [
            (object) [
                'ownerId' => '123e4567-e89b-12d3-a456-426614174002',
                'position' => ['x' => 6, 'y' => 6],
                'type' => 'warrior'
            ],
            (object) [
                'ownerId' => '123e4567-e89b-12d3-a456-426614174005',
                'position' => ['x' => 7, 'y' => 7],
                'type' => 'scout'
            ]
        ];

        $cities = [
            (object) [
                'ownerId' => '123e4567-e89b-12d3-a456-426614174002',
                'position' => ['x' => 5, 'y' => 5],
                'level' => 1
            ],
            (object) [
                'ownerId' => '123e4567-e89b-12d3-a456-426614174005',
                'position' => ['x' => 8, 'y' => 8],
                'level' => 2
            ]
        ];

        $callCount = 0;
        $this->queryBus->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function ($query) use ($units, $cities, &$callCount) {
                if ($callCount === 0) {
                    $this->assertInstanceOf(GetUnitsByPlayerQuery::class, $query);
                    $this->assertEquals('123e4567-e89b-12d3-a456-426614174002', (string)$query->playerId);
                    $callCount++;
                    return $units;
                } else {
                    $this->assertInstanceOf(GetCitiesByPlayerQuery::class, $query);
                    $this->assertEquals('123e4567-e89b-12d3-a456-426614174002', (string)$query->playerId);
                    return $cities;
                }
            });

        $this->visibilityService->expects($this->once())
            ->method('updatePlayerVisibility')
            ->with(
                $this->callback(fn(PlayerId $id) => (string)$id === '123e4567-e89b-12d3-a456-426614174002'),
                $units,
                $cities
            );

        $this->handler->handle($event);
    }

    public function testHandleWithNoPlayerUnits(): void
    {
        $event = new UnitWasMoved(
            '123e4567-e89b-12d3-a456-426614174001',
            '123e4567-e89b-12d3-a456-426614174002',
            5,
            5,
            6,
            6,
            '2024-01-01T00:00:00Z'
        );

        $units = [
            (object) [
                'ownerId' => '123e4567-e89b-12d3-a456-426614174005',
                'position' => ['x' => 7, 'y' => 7],
                'type' => 'scout'
            ]
        ];

        $cities = [
            (object) [
                'ownerId' => '123e4567-e89b-12d3-a456-426614174002',
                'position' => ['x' => 5, 'y' => 5],
                'level' => 1
            ]
        ];

        $callCount = 0;
        $this->queryBus->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function ($query) use ($units, $cities, &$callCount) {
                if ($callCount === 0) {
                    $this->assertInstanceOf(GetUnitsByPlayerQuery::class, $query);
                    $callCount++;
                    return $units;
                } else {
                    $this->assertInstanceOf(GetCitiesByPlayerQuery::class, $query);
                    return $cities;
                }
            });

        $this->visibilityService->expects($this->once())
            ->method('updatePlayerVisibility')
            ->with(
                $this->callback(fn(PlayerId $id) => (string)$id === '123e4567-e89b-12d3-a456-426614174002'),
                $units,
                $cities
            );

        $this->handler->handle($event);
    }

    public function testHandleWithMultiplePlayerUnits(): void
    {
        $event = new UnitWasMoved(
            '123e4567-e89b-12d3-a456-426614174001',
            '123e4567-e89b-12d3-a456-426614174002',
            5,
            5,
            6,
            6,
            '2024-01-01T00:00:00Z'
        );

        $units = [
            (object) [
                'ownerId' => '123e4567-e89b-12d3-a456-426614174002',
                'position' => ['x' => 6, 'y' => 6],
                'type' => 'warrior'
            ],
            (object) [
                'ownerId' => '123e4567-e89b-12d3-a456-426614174002',
                'position' => ['x' => 7, 'y' => 7],
                'type' => 'scout'
            ],
            (object) [
                'ownerId' => '123e4567-e89b-12d3-a456-426614174005',
                'position' => ['x' => 8, 'y' => 8],
                'type' => 'archer'
            ]
        ];

        $cities = [
            (object) [
                'ownerId' => '123e4567-e89b-12d3-a456-426614174002',
                'position' => ['x' => 5, 'y' => 5],
                'level' => 1
            ]
        ];

        $callCount = 0;
        $this->queryBus->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function ($query) use ($units, $cities, &$callCount) {
                if ($callCount === 0) {
                    $this->assertInstanceOf(GetUnitsByPlayerQuery::class, $query);
                    $callCount++;
                    return $units;
                } else {
                    $this->assertInstanceOf(GetCitiesByPlayerQuery::class, $query);
                    return $cities;
                }
            });

        $this->visibilityService->expects($this->once())
            ->method('updatePlayerVisibility')
            ->with(
                $this->callback(fn(PlayerId $id) => (string)$id === '123e4567-e89b-12d3-a456-426614174002'),
                $units,
                $cities
            );

        $this->handler->handle($event);
    }
} 