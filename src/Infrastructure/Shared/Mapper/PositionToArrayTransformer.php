<?php

namespace App\Infrastructure\Shared\Mapper;

use Symfony\Component\ObjectMapper\TransformCallableInterface;

final class PositionToArrayTransformer implements TransformCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        return ['x' => $source->x, 'y' => $source->y];
    }
}
