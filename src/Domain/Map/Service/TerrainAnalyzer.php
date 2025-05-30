<?php

namespace App\Domain\Map\Service;

use App\Domain\Map\ValueObject\TerrainProperties;

/**
 * TerrainAnalyzer - domain service for terrain analysis and tactical evaluation
 *
 * Contains all analytical logic that was previously in TerrainProperties.
 * Provides expert knowledge about terrain tactical values, strategic importance,
 * and composite scoring algorithms.
 */
class TerrainAnalyzer
{
    /**
     * Calculates overall terrain value using weighted scoring
     */
    public function calculateOverallValue(TerrainProperties $properties): int
    {
        $value = 0;
        $value += $properties->resourceYield * 2; // Economic value is weighted higher
        $value += $properties->defenseBonus;
        $value += $properties->isPassable ? 1 : -2; // Passability bonus/penalty
        $value -= $properties->movementCost; // Movement difficulty penalty

        return max(0, $value);
    }

    /**
     * Calculates tactical score based on military and strategic factors
     */
    public function calculateTacticalScore(TerrainProperties $properties): float
    {
        $score = 0.0;

        if ($properties->isPassable) {
            $score += 2.0;
        }

        if ($this->isEasyToTraverse($properties)) {
            $score += 1.5;
        } elseif ($this->isDifficultToTraverse($properties)) {
            $score -= 1.0;
        }

        if ($this->isFortified($properties)) {
            $score += 2.0;
        }

        if ($this->isResourceRich($properties)) {
            $score += 1.5;
        }

        return $score;
    }

    /**
     * Determines if terrain provides total tactical advantage
     */
    public function providesTotalAdvantage(TerrainProperties $properties): bool
    {
        return $this->isTacticallyAdvantaged($properties) && $this->isEconomicallyViable($properties);
    }

    /**
     * Identifies high-value targets for strategic planning
     */
    public function isHighValueTarget(TerrainProperties $properties): bool
    {
        return $this->isStrategicallyImportant($properties) || $this->isResourceRich($properties);
    }

    /**
     * Evaluates defensive position value
     */
    public function isDefensivePosition(TerrainProperties $properties): bool
    {
        return $properties->defenseBonus >= 2;
    }

    /**
     * Checks if terrain allows quick traversal
     */
    public function allowsQuickTraversal(TerrainProperties $properties): bool
    {
        return $properties->movementCost <= 1 && $properties->isPassable;
    }

    /**
     * Determines if terrain requires special movement considerations
     */
    public function requiresSpecialMovement(TerrainProperties $properties): bool
    {
        return $properties->movementCost >= 3 || !$properties->isPassable;
    }

    /**
     * Comprehensive analysis combining all factors
     */
    public function getComprehensiveAnalysis(TerrainProperties $properties): array
    {
        return [
            'overall_value' => $this->calculateOverallValue($properties),
            'tactical_score' => $this->calculateTacticalScore($properties),
            'strategic_importance' => $this->isStrategicallyImportant($properties),
            'tactical_advantages' => [
                'provides_total_advantage' => $this->providesTotalAdvantage($properties),
                'is_high_value_target' => $this->isHighValueTarget($properties),
                'is_defensive_position' => $this->isDefensivePosition($properties),
                'allows_quick_traversal' => $this->allowsQuickTraversal($properties),
                'requires_special_movement' => $this->requiresSpecialMovement($properties)
            ],
            'characteristics' => [
                'tactically_advantaged' => $this->isTacticallyAdvantaged($properties),
                'economically_viable' => $this->isEconomicallyViable($properties),
                'easy_to_traverse' => $this->isEasyToTraverse($properties),
                'difficult_to_traverse' => $this->isDifficultToTraverse($properties),
                'fortified' => $this->isFortified($properties),
                'resource_rich' => $this->isResourceRich($properties)
            ]
        ];
    }

    /**
     * Gets movement difficulty classification
     */
    public function getMovementDifficultyLevel(TerrainProperties $properties): string
    {
        return match ($properties->movementCost) {
            0 => 'Impassable',
            1 => 'Easy',
            2 => 'Moderate',
            3 => 'Difficult',
            4 => 'Very Difficult',
            default => 'Extremely Difficult'
        };
    }

    /**
     * Gets defensive capability classification
     */
    public function getDefensiveLevel(TerrainProperties $properties): string
    {
        return match ($properties->defenseBonus) {
            0 => 'None',
            1 => 'Minor',
            2 => 'Moderate',
            3 => 'Strong',
            4 => 'Fortress Level 1',
            5 => 'Fortress Level 2',
            default => 'Heavily Fortified'
        };
    }

    /**
     * Gets economic value classification
     */
    public function getEconomicLevel(TerrainProperties $properties): string
    {
        return match ($properties->resourceYield) {
            0 => 'None',
            1 => 'Poor',
            2 => 'Moderate',
            3 => 'Rich',
            4 => 'Abundant Level 1',
            5 => 'Abundant Level 2',
            default => 'Exceptional'
        };
    }

    // Private helper methods for complex domain logic

    private function isTacticallyAdvantaged(TerrainProperties $properties): bool
    {
        return $this->isFortified($properties);
    }

    private function isEconomicallyViable(TerrainProperties $properties): bool
    {
        return $properties->resourceYield >= 3;
    }

    private function isStrategicallyImportant(TerrainProperties $properties): bool
    {
        return $properties->defenseBonus >= 3 || $properties->resourceYield >= 3;
    }

    private function isEasyToTraverse(TerrainProperties $properties): bool
    {
        return $properties->movementCost === 1 && $properties->isPassable;
    }

    private function isDifficultToTraverse(TerrainProperties $properties): bool
    {
        return $properties->movementCost >= 3;
    }

    private function isFortified(TerrainProperties $properties): bool
    {
        return $properties->defenseBonus >= 4;
    }

    private function isResourceRich(TerrainProperties $properties): bool
    {
        return $properties->resourceYield >= 4;
    }
}
