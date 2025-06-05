<?php

namespace App\Infrastructure\Shared\Mapper;

use App\Infrastructure\City\ReadModel\Doctrine\CityViewEntity;
use App\UI\City\ViewModel\CityView;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * @implements TransformCallableInterface<CityViewEntity, CityView>
 */
final class PositionToArrayTransformer implements TransformCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        /** @var CityViewEntity $source */
        return ['x' => $source->x, 'y' => $source->y];
    }
}
