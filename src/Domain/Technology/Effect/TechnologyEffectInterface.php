<?php

namespace App\Domain\Technology\Effect;
interface TechnologyEffectInterface
{
    public function apply(mixed $context): void;

    public function getName(): string;

    public function getDescription(): string;
}
