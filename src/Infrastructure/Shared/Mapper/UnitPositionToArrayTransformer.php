<?php

namespace App\Infrastructure\Shared\Mapper;

use App\Infrastructure\Unit\ReadModel\Doctrine\UnitViewEntity;
use App\UI\Unit\ViewModel\UnitView;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * @implements TransformCallableInterface<UnitViewEntity, UnitView>
 */
final class UnitPositionToArrayTransformer implements TransformCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        /** @var UnitViewEntity $source */
        return ['x' => $source->x, 'y' => $source->y];
    }
} 