<?php

namespace App\Domain\Technology\Policy;

use App\Domain\Technology\Technology;
use App\Domain\Technology\ValueObject\TechnologyId;

final readonly class TechnologyPrerequisitesPolicy
{
    public function arePrerequisitesMet(Technology $technology, array $unlockedTechnologies): bool
    {
        if (!$technology->hasPrerequisites()) {
            return true;
        }
        $unlockedIds = array_map(fn(TechnologyId $id) => (string)$id, $unlockedTechnologies);
        foreach ($technology->prerequisites as $prerequisite) {
            if (!in_array((string)$prerequisite, $unlockedIds, true)) {
                return false;
            }
        }
        return true;
    }

    public function getMissingPrerequisites(Technology $technology, array $unlockedTechnologies): array
    {
        if (!$technology->hasPrerequisites()) {
            return [];
        }
        $unlockedIds = array_map(fn(TechnologyId $id) => (string)$id, $unlockedTechnologies);
        $missing = [];
        foreach ($technology->prerequisites as $prerequisite) {
            if (!in_array((string)$prerequisite, $unlockedIds, true)) {
                $missing[] = $prerequisite;
            }
        }
        return $missing;
    }
}
