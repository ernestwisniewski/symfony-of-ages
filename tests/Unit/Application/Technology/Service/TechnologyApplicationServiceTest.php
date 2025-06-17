<?php

namespace Tests\Unit\Application\Technology\Service;

use App\Application\Technology\Service\TechnologyApplicationService;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Technology\Repository\TechnologyRepository;
use App\Domain\Technology\Repository\TechnologyTreeRepository;
use App\Domain\Technology\Service\TechnologyManagementService;
use App\Domain\Technology\Technology;
use App\Domain\Technology\TechnologyTree;
use App\Domain\Technology\ValueObject\TechnologyId;
use App\UI\Technology\ViewModel\TechnologyTreeView;
use App\UI\Technology\ViewModel\TechnologyView;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class TechnologyApplicationServiceTest extends TestCase
{
    private TechnologyApplicationService $service;
    private TechnologyManagementService $managementService;
    private TechnologyRepository $technologyRepository;
    private TechnologyTreeRepository $technologyTreeRepository;
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    protected function setUp(): void
    {
        $this->managementService = $this->createMock(TechnologyManagementService::class);
        $this->technologyRepository = $this->createMock(TechnologyRepository::class);
        $this->technologyTreeRepository = $this->createMock(TechnologyTreeRepository::class);
        $this->commandBus = $this->createMock(CommandBus::class);
        $this->queryBus = $this->createMock(QueryBus::class);

        $this->service = new TechnologyApplicationService(
            $this->managementService,
            $this->technologyRepository,
            $this->technologyTreeRepository,
            $this->commandBus,
            $this->queryBus
        );
    }

    private function uuid(): string
    {
        return Uuid::v4()->toRfc4122();
    }

    public function testDiscoverTechnologySuccess(): void
    {
        $playerId = new PlayerId($this->uuid());
        $technologyId = new TechnologyId($this->uuid());
        $gameId = new GameId($this->uuid());
        $technology = $this->createMock(Technology::class);
        $technologyTree = $this->createMock(TechnologyTree::class);

        $this->technologyRepository->expects($this->once())
            ->method('findBy')
            ->with($technologyId)
            ->willReturn($technology);

        $this->technologyTreeRepository->expects($this->once())
            ->method('findBy')
            ->with($playerId)
            ->willReturn($technologyTree);

        $this->managementService->expects($this->once())
            ->method('canDiscoverTechnology')
            ->willReturn(true);

        $this->commandBus->expects($this->once())
            ->method('send');

        $result = $this->service->discoverTechnology($playerId, $technologyId, $gameId);

        $this->assertTrue($result['success']);
        $this->assertEquals('Technology discovered successfully', $result['message']);
    }

    public function testDiscoverTechnologyNotFound(): void
    {
        $playerId = new PlayerId($this->uuid());
        $technologyId = new TechnologyId($this->uuid());
        $gameId = new GameId($this->uuid());

        $this->technologyRepository->expects($this->once())
            ->method('findBy')
            ->with($technologyId)
            ->willReturn(null);

        $result = $this->service->discoverTechnology($playerId, $technologyId, $gameId);

        $this->assertFalse($result['success']);
        $this->assertEquals('Technology not found', $result['reason']);
    }

    public function testGetTechnologyTree(): void
    {
        $playerId = new PlayerId($this->uuid());
        $technologyTreeView = new TechnologyTreeView();
        $technologyTreeView->playerId = (string)$playerId;
        $technologyTreeView->unlockedTechnologies = ['tech-1', 'tech-2'];
        $technologyTreeView->availableTechnologies = ['tech-3', 'tech-4'];
        $technologyTreeView->sciencePoints = 100;

        $this->queryBus->expects($this->once())
            ->method('send')
            ->willReturn($technologyTreeView);

        $result = $this->service->getTechnologyTree($playerId);

        $this->assertArrayHasKey('unlockedTechnologies', $result);
        $this->assertArrayHasKey('availableTechnologies', $result);
        $this->assertArrayHasKey('sciencePoints', $result);
        $this->assertEquals(['tech-1', 'tech-2'], $result['unlockedTechnologies']);
        $this->assertEquals(['tech-3', 'tech-4'], $result['availableTechnologies']);
        $this->assertEquals(100, $result['sciencePoints']);
    }

    public function testGetAllTechnologies(): void
    {
        $technologyViews = [
            new TechnologyView(),
            new TechnologyView()
        ];

        $this->queryBus->expects($this->once())
            ->method('send')
            ->willReturn($technologyViews);

        $result = $this->service->getAllTechnologies();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }
}
