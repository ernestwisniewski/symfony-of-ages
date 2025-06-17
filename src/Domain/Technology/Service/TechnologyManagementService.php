<?php

namespace App\Domain\Technology\Service;

use App\Domain\Technology\Policy\TechnologyPrerequisitesPolicy;
use App\Domain\Technology\Technology;
use App\Domain\Technology\ValueObject\TechnologyId;

class TechnologyManagementService
{
    public function __construct(
        private TechnologyPrerequisitesPolicy $prerequisitesPolicy
    )
    {
    }

    public function canDiscoverTechnology(
        Technology $technology,
        array      $unlockedTechnologies,
        int        $availableSciencePoints
    ): bool
    {
        $unlockedIds = array_map(fn(TechnologyId $id) => (string)$id, $unlockedTechnologies);
        if (in_array((string)$technology->id, $unlockedIds, true)) {
            return false;
        }
        if (!$this->prerequisitesPolicy->arePrerequisitesMet($technology, $unlockedTechnologies)) {
            return false;
        }
        if ($availableSciencePoints < $technology->cost) {
            return false;
        }
        return true;
    }

    public function getAvailableTechnologies(
        array $allTechnologies,
        array $unlockedTechnologies,
        int   $availableSciencePoints
    ): array
    {
        return array_filter(
            $allTechnologies,
            fn(Technology $technology) => $this->canDiscoverTechnology(
                $technology,
                $unlockedTechnologies,
                $availableSciencePoints
            )
        );
    }

    public function getFutureTechnologies(
        array $allTechnologies,
        array $unlockedTechnologies,
        int   $availableSciencePoints
    ): array
    {
        return array_filter(
            $allTechnologies,
            function (Technology $technology) use ($unlockedTechnologies, $availableSciencePoints) {
                $unlockedIds = array_map(fn(TechnologyId $id) => (string)$id, $unlockedTechnologies);
                if (in_array((string)$technology->id, $unlockedIds, true)) {
                    return false;
                }
                if (!$this->prerequisitesPolicy->arePrerequisitesMet($technology, $unlockedTechnologies)) {
                    return false;
                }
                return $availableSciencePoints < $technology->cost;
            }
        );
    }
}
