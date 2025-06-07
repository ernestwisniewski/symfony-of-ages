<?php

namespace App\UI\Unit\Http\Api;

use App\Application\Unit\Query\GetUnitViewQuery;
use App\Domain\Unit\ValueObject\UnitId;
use Ecotone\Modelling\QueryBus;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
readonly class GetUnitController
{
    public function __construct(private QueryBus $queryBus)
    {
    }

    #[Route('/api/unit/{unitId}', name: 'app_unit_get', methods: ['GET'])]
    public function __invoke(string $unitId): Response
    {
        try {
            $unitView = $this->queryBus->send(new GetUnitViewQuery(new UnitId($unitId)));
            return new JsonResponse($unitView);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => 'Unit not found'], 404);
        }
    }
}
