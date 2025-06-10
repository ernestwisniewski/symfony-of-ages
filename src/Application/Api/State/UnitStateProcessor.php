<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Unit\Command\AttackUnitCommand;
use App\Application\Unit\Command\CreateUnitCommand;
use App\Application\Unit\Command\MoveUnitCommand;
use App\Application\Unit\Dto\TargetUnitDto;
use App\Application\Unit\Query\GetUnitViewQuery;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\Health;
use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Unit\ValueObject\UnitType;
use App\UI\Api\Resource\UnitResource;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

final readonly class UnitStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus   $queryBus
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $uriTemplate = $operation->getUriTemplate();

        match (true) {
            str_contains($uriTemplate, '/games/{gameId}/units') && $operation->getMethod() === 'POST' => $this->createUnit($data, $uriVariables['gameId'], $operation),
            str_contains($uriTemplate, '/units/{unitId}/move') => $this->moveUnit($data, $uriVariables['unitId']),
            str_contains($uriTemplate, '/units/{unitId}/attack') => $this->attackUnit($data, $uriVariables['unitId'], $operation),
            default => throw new BadRequestHttpException('Unsupported operation'),
        };
    }

    private function createUnit(UnitResource $data, string $gameId): void
    {
        $unitId = new UnitId(Uuid::v4()->toRfc4122());
        $unitType = UnitType::from($data->unitType);
        $createdAt = Timestamp::now();

        $command = new CreateUnitCommand(
            unitId: $unitId,
            ownerId: new PlayerId($data->playerId),
            gameId: new GameId($gameId),
            type: $unitType,
            position: new Position($data->x, $data->y),
            createdAt: $createdAt
        );

        $this->commandBus->send($command);
    }

    private function moveUnit(UnitResource $data, string $unitId): void
    {
        $movedAt = Timestamp::now();

        $command = new MoveUnitCommand(
            unitId: new UnitId($unitId),
            toPosition: new Position($data->toX, $data->toY),
            existingUnits: [],
            movedAt: $movedAt
        );

        $this->commandBus->send($command);
    }

    private function attackUnit(UnitResource $data, string $unitId): void
    {
        $targetUnit = $this->queryBus->send(new GetUnitViewQuery(new UnitId($data->targetUnitId)));

        $targetDto = new TargetUnitDto(
            unitId: new UnitId($data->targetUnitId),
            ownerId: new PlayerId($targetUnit->ownerId),
            position: new Position($targetUnit->position['x'], $targetUnit->position['y']),
            type: UnitType::from($targetUnit->type),
            health: new Health($targetUnit->currentHealth, $targetUnit->maxHealth)
        );

        $command = new AttackUnitCommand(
            unitId: new UnitId($unitId),
            targetUnit: $targetDto,
            attackedAt: Timestamp::now()
        );

        $this->commandBus->send($command);
    }
}
