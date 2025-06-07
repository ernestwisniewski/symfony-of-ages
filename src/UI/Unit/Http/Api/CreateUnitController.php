<?php

namespace App\UI\Unit\Http\Api;

use App\Application\Unit\Command\CreateUnitCommand;
use App\Domain\City\ValueObject\Position;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;
use App\Domain\Unit\ValueObject\UnitType;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use ValueError;

#[AsController]
readonly class CreateUnitController
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    #[Route('/api/game/{gameId}/{playerId}/create_unit/{unitType}', name: 'app_unit_create', methods: ['GET'])]
    public function __invoke(string $gameId, string $playerId, string $unitType): Response
    {
        // In a real application, you would:
        // 1. Validate that the player can create units (e.g., near their city)
        // 2. Check if they have enough resources
        // 3. Get a valid position from request or find suitable location

        // For demo purposes, placing at random position
        $position = new Position(rand(1, 10), rand(1, 10));

        try {
            $type = UnitType::from($unitType);
        } catch (ValueError) {
            return new JsonResponse(['error' => 'Invalid unit type'], 400);
        }

        $this->commandBus->send(
            new CreateUnitCommand(
                new UnitId(Uuid::v4()->toRfc4122()),
                new PlayerId($playerId),
                new GameId($gameId),
                $type,
                $position,
                Timestamp::now()
            )
        );

        return new JsonResponse(['success' => true, 'message' => "Unit {$unitType} created successfully"]);
    }
}
