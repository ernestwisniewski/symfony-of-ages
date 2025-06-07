<?php

namespace App\UI\Unit\Http\Api;

use App\Application\Unit\Command\MoveUnitCommand;
use App\Domain\City\ValueObject\Position;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Unit\ValueObject\UnitId;
use DomainException;
use Ecotone\Modelling\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
readonly class MoveUnitController
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    #[Route('/api/unit/{unitId}/move', name: 'app_unit_move', methods: ['POST'])]
    public function __invoke(string $unitId, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['x'], $data['y'])) {
            return new JsonResponse(['error' => 'Missing x or y coordinates'], 400);
        }

        $toPosition = new Position((int)$data['x'], (int)$data['y']);

        // In a real application, you would get existing units from a repository
        $existingUnits = []; // TODO: Get from repository to check for collisions

        try {
            $this->commandBus->send(
                new MoveUnitCommand(
                    new UnitId($unitId),
                    $toPosition,
                    $existingUnits,
                    Timestamp::now()
                )
            );

            return new JsonResponse([
                'success' => true,
                'message' => "Unit moved to ({$data['x']}, {$data['y']})"
            ]);
        } catch (DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
