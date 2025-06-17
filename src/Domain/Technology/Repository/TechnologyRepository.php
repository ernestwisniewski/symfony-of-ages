<?php

namespace App\Domain\Technology\Repository;

use App\Domain\Technology\Technology;
use App\Domain\Technology\ValueObject\TechnologyId;

interface TechnologyRepository
{
    public function findBy(TechnologyId $technologyId): ?Technology;

    public function findAll(): array;

    public function findByIds(array $technologyIds): array;
}
