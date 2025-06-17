<?php

namespace App\Domain\Map\Service;

use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Shared\ValueObject\ValidationConstants;

class TerrainAnalyzer
{
    public function calculateOverallValue(TerrainType $terrainType): int
    {
        $value = 0;
        $value += $terrainType->getResourceYield() * 2;
        $value += $terrainType->getDefenseBonus();
        $value += $terrainType->isPassable() ? 1 : -2;
        $value -= $terrainType->getMovementCost();

        return max(0, $value);
    }

    public function calculateTacticalScore(TerrainType $terrainType): float
    {
        $score = 0.0;

        if ($terrainType->isPassable()) {
            $score += 2.0;
        }

        if ($this->isEasyToTraverse($terrainType)) {
            $score += 1.5;
        } elseif ($this->isDifficultToTraverse($terrainType)) {
            $score -= 1.0;
        }

        if ($this->isFortified($terrainType)) {
            $score += 2.0;
        }

        if ($this->isResourceRich($terrainType)) {
            $score += 1.5;
        }

        return $score;
    }

    public function providesTotalAdvantage(TerrainType $terrainType): bool
    {
        return $this->isTacticallyAdvantaged($terrainType) && $this->isEconomicallyViable($terrainType);
    }

    public function isHighValueTarget(TerrainType $terrainType): bool
    {
        return $this->isStrategicallyImportant($terrainType) || $this->isResourceRich($terrainType);
    }

    public function isDefensivePosition(TerrainType $terrainType): bool
    {
        return $terrainType->getDefenseBonus() >= ValidationConstants::MIN_DEFENSE_BONUS_FOR_DEFENSIVE_POSITION;
    }

    public function allowsQuickTraversal(TerrainType $terrainType): bool
    {
        return $terrainType->getMovementCost() <= ValidationConstants::MAX_MOVEMENT_COST_FOR_QUICK_TRAVERSAL && $terrainType->isPassable();
    }

    public function requiresSpecialMovement(TerrainType $terrainType): bool
    {
        return $terrainType->getMovementCost() >= ValidationConstants::MIN_MOVEMENT_COST_FOR_SPECIAL_MOVEMENT || !$terrainType->isPassable();
    }

    public function getComprehensiveAnalysis(TerrainType $terrainType): array
    {
        return [
            'overall_value' => $this->calculateOverallValue($terrainType),
            'tactical_score' => $this->calculateTacticalScore($terrainType),
            'strategic_importance' => $this->isStrategicallyImportant($terrainType),
            'tactical_advantages' => [
                'provides_total_advantage' => $this->providesTotalAdvantage($terrainType),
                'is_high_value_target' => $this->isHighValueTarget($terrainType),
                'is_defensive_position' => $this->isDefensivePosition($terrainType),
                'allows_quick_traversal' => $this->allowsQuickTraversal($terrainType),
                'requires_special_movement' => $this->requiresSpecialMovement($terrainType)
            ],
            'characteristics' => [
                'tactically_advantaged' => $this->isTacticallyAdvantaged($terrainType),
                'economically_viable' => $this->isEconomicallyViable($terrainType),
                'easy_to_traverse' => $this->isEasyToTraverse($terrainType),
                'difficult_to_traverse' => $this->isDifficultToTraverse($terrainType),
                'fortified' => $this->isFortified($terrainType),
                'resource_rich' => $this->isResourceRich($terrainType)
            ]
        ];
    }

    public function getMovementDifficultyLevel(TerrainType $terrainType): string
    {
        return match ($terrainType->getMovementCost()) {
            0 => 'Impassable',
            1 => 'Easy',
            2 => 'Moderate',
            3 => 'Difficult',
            4 => 'Very Difficult',
            default => 'Extremely Difficult'
        };
    }

    public function getDefensiveLevel(TerrainType $terrainType): string
    {
        return match ($terrainType->getDefenseBonus()) {
            0 => 'None',
            1 => 'Minor',
            2 => 'Moderate',
            3 => 'Strong',
            4 => 'Fortress Level 1',
            5 => 'Fortress Level 2',
            default => 'Heavily Fortified'
        };
    }

    public function getEconomicLevel(TerrainType $terrainType): string
    {
        return match ($terrainType->getResourceYield()) {
            0 => 'None',
            1 => 'Poor',
            2 => 'Moderate',
            3 => 'Rich',
            4 => 'Abundant Level 1',
            5 => 'Abundant Level 2',
            default => 'Exceptional'
        };
    }

    private function isTacticallyAdvantaged(TerrainType $terrainType): bool
    {
        return $this->isFortified($terrainType);
    }

    private function isEconomicallyViable(TerrainType $terrainType): bool
    {
        return $terrainType->getResourceYield() >= ValidationConstants::MIN_RESOURCE_YIELD_FOR_ECONOMICALLY_VIABLE;
    }

    private function isStrategicallyImportant(TerrainType $terrainType): bool
    {
        return $terrainType->getDefenseBonus() >= ValidationConstants::MIN_DEFENSE_BONUS_FOR_STRATEGIC_IMPORTANCE || $terrainType->getResourceYield() >= ValidationConstants::MIN_RESOURCE_YIELD_FOR_STRATEGIC_IMPORTANCE;
    }

    private function isEasyToTraverse(TerrainType $terrainType): bool
    {
        return $terrainType->getMovementCost() === ValidationConstants::MIN_MOVEMENT_COST_FOR_QUICK_TRAVERSAL && $terrainType->isPassable();
    }

    private function isDifficultToTraverse(TerrainType $terrainType): bool
    {
        return $terrainType->getMovementCost() >= ValidationConstants::MIN_MOVEMENT_COST_FOR_DIFFICULT_TRAVERSAL;
    }

    private function isFortified(TerrainType $terrainType): bool
    {
        return $terrainType->getDefenseBonus() >= ValidationConstants::MIN_DEFENSE_BONUS_FOR_FORTIFIED;
    }

    private function isResourceRich(TerrainType $terrainType): bool
    {
        return $terrainType->getResourceYield() >= ValidationConstants::MIN_RESOURCE_YIELD_FOR_RESOURCE_RICH;
    }
}
