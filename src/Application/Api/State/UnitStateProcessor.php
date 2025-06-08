<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Api\Resource\UnitResource;
use App\Application\Unit\Command\AttackUnitCommand;
use App\Application\Unit\Command\CreateUnitCommand;
use App\Application\Unit\Command\MoveUnitCommand;
use App\Application\Unit\Dto\TargetUnitDto;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\Health;
use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Unit\ValueObject\UnitType;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

final readonly class UnitStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBus        $commandBus,
        private UnitStateProvider $unitStateProvider,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UnitResource
    {
        if (!$data instanceof UnitResource) {
            throw new BadRequestHttpException('Invalid data type');
        }

        $uriTemplate = $operation->getUriTemplate();

        return match (true) {
            str_contains($uriTemplate, '/games/{gameId}/units') && $operation->getMethod() === 'POST' => $this->createUnit($data, $uriVariables['gameId'], $operation),
            str_contains($uriTemplate, '/units/{unitId}/move') => $this->moveUnit($data, $uriVariables['unitId'], $operation),
            str_contains($uriTemplate, '/units/{unitId}/attack') => $this->attackUnit($data, $uriVariables['unitId'], $operation),
            default => throw new BadRequestHttpException('Unsupported operation'),
        };
    }

    private function createUnit(UnitResource $data, string $gameId, Operation $operation): UnitResource|null
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

        // CQRS: Commands should not return data, only execute actions
        return null;
    }

    private function moveUnit(UnitResource $data, string $unitId, Operation $operation): UnitResource
    {
        $movedAt = Timestamp::now();

        $command = new MoveUnitCommand(
            unitId: new UnitId($unitId),
            toPosition: new Position($data->toX, $data->toY),
            existingUnits: [],
            movedAt: $movedAt
        );

        $this->commandBus->send($command);

        // Try to get updated unit, fallback to current operation if projection not ready
        $unitResource = $this->unitStateProvider->provide(
            $operation,
            ['unitId' => $unitId]
        );

        if ($unitResource === null) {
            throw new BadRequestHttpException('Unit not found after move operation');
        }

        return $unitResource;
    }

    private function attackUnit(UnitResource $data, string $unitId, Operation $operation): UnitResource|null
    {
        // Create a temporary operation for getting target unit
        $getOperation = new \ApiPlatform\Metadata\Get(
            uriTemplate: '/units/{unitId}',
            provider: UnitStateProvider::class
        );

        // Get target unit info for DTO
        $targetUnit = $this->unitStateProvider->provide(
            $getOperation,
            ['unitId' => $data->targetUnitId]
        );

        if (!$targetUnit) {
            throw new BadRequestHttpException('Target unit not found');
        }

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

        // CQRS: Commands should not return data, only execute actions
        return null;
    }
}
