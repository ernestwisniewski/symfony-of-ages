<?php

declare(strict_types=1);

namespace App\UI\City\Http\Api;

use App\Application\City\Query\GetCityViewQuery;
use App\Domain\City\ValueObject\CityId;
use Ecotone\Modelling\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CityCityController extends AbstractController
{
    public function __construct(private readonly QueryBus $queryBus)
    {
    }

    #[Route('/api/city/{cityId}')]
    public function index(string $cityId): Response
    {
        $viewModel = $this->queryBus->send(new GetCityViewQuery(new CityId($cityId)));

        return new JsonResponse($viewModel);
    }
}
