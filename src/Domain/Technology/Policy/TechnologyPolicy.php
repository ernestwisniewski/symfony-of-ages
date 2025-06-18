<?php

namespace App\Domain\Technology\Policy;

use App\Domain\Technology\ValueObject\TechnologyId;

final readonly class TechnologyPolicy
{
    public function arePrerequisitesMet(array $technology, array $unlockedTechnologies): bool
    {
        if (empty($technology['prerequisites'])) {
            return true;
        }
        $unlockedIds = array_map(fn(TechnologyId $id) => (string)$id, $unlockedTechnologies);
        foreach ($technology['prerequisites'] as $prerequisite) {
            if (!in_array($prerequisite->value, $unlockedIds, true)) {
                return false;
            }
        }
        return true;
    }

    public function getMissingPrerequisites(array $technology, array $unlockedTechnologies): array
    {
        if (empty($technology['prerequisites'])) {
            return [];
        }
        $unlockedIds = array_map(fn(TechnologyId $id) => (string)$id, $unlockedTechnologies);
        $missing = [];
        foreach ($technology['prerequisites'] as $prerequisite) {
            if (!in_array($prerequisite->value, $unlockedIds, true)) {
                $missing[] = $prerequisite;
            }
        }
        return $missing;
    }
}
