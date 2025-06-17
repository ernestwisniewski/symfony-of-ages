<?php

namespace App\Infrastructure\Technology\Repository;

use App\Domain\Technology\Repository\TechnologyRepository;
use App\Domain\Technology\Service\TechnologyFactory;
use App\Domain\Technology\Technology;
use App\Domain\Technology\ValueObject\TechnologyId;

final readonly class TechnologyDefinitionRepository implements TechnologyRepository
{
    private array $technologies;

    public function __construct(
        private TechnologyFactory $technologyFactory
    )
    {
        $this->technologies = $this->initializeTechnologies();
    }

    public function findBy(TechnologyId $technologyId): ?Technology
    {
        return $this->technologies[(string)$technologyId] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->technologies);
    }

    public function findByIds(array $technologyIds): array
    {
        $result = [];
        foreach ($technologyIds as $technologyId) {
            $technology = $this->findBy($technologyId);
            if ($technology) {
                $result[] = $technology;
            }
        }
        return $result;
    }

    public function findById(TechnologyId $technologyId): ?Technology
    {
        return $this->findBy($technologyId);
    }

    private function initializeTechnologies(): array
    {
        $allTechnologies = $this->technologyFactory->createAllTechnologies();
        $technologies = [];
        foreach ($allTechnologies as $technology) {
            $technologies[(string)$technology->id] = $technology;
        }
        return $technologies;
    }
}
