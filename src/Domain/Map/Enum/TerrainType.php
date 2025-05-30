<?php

namespace App\Domain\Map\Enum;

use App\Domain\Map\ValueObject\TerrainCombatProperties;
use App\Domain\Map\ValueObject\TerrainEconomicProperties;
use App\Domain\Map\ValueObject\TerrainMovementProperties;
use App\Domain\Map\ValueObject\TerrainProperties;
use App\Domain\Map\ValueObject\TerrainVisualProperties;

/**
 * TerrainType enum represents different types of terrain on the game map
 *
 * Clean enum that only handles the mapping between terrain type values
 * and their properties. All analysis logic has been moved to TerrainProperties
 * for better separation of concerns and cleaner architecture.
 *
 * Each terrain type has distinct characteristics that affect gameplay:
 * - Movement cost (how many movement points needed to enter)
 * - Defense bonus (combat advantage when positioned here)
 * - Resource yield (economic value generated)
 * - Visual representation (name and color for display)
 */
enum TerrainType: string
{
    /** @var string Plains terrain - basic grassland with moderate properties */
    case PLAINS = 'plains';

    /** @var string Forest terrain - wooded areas with high defense and resources */
    case FOREST = 'forest';

    /** @var string Mountain terrain - elevated areas with highest defense and resources */
    case MOUNTAIN = 'mountain';

    /** @var string Water terrain - impassable water bodies */
    case WATER = 'water';

    /** @var string Desert terrain - arid areas with low resources */
    case DESERT = 'desert';

    /** @var string Swamp terrain - marshy areas with high movement cost */
    case SWAMP = 'swamp';

    /**
     * Gets complete terrain properties as structured Value Objects
     *
     * Returns TerrainProperties aggregate containing specialized value objects
     * for different aspects of terrain (visual, movement, combat, economic).
     * This provides better type safety and separation of concerns.
     *
     * @return TerrainProperties Complete terrain characteristics
     */
    public function getProperties(): TerrainProperties
    {
        return match ($this) {
            self::PLAINS => new TerrainProperties(
                new TerrainVisualProperties('Plains', 0x90EE90),
                new TerrainMovementProperties(1),
                new TerrainCombatProperties(1),
                new TerrainEconomicProperties(2)
            ),
            self::FOREST => new TerrainProperties(
                new TerrainVisualProperties('Forest', 0x228B22),
                new TerrainMovementProperties(2),
                new TerrainCombatProperties(3),
                new TerrainEconomicProperties(3)
            ),
            self::MOUNTAIN => new TerrainProperties(
                new TerrainVisualProperties('Mountain', 0x808080),
                new TerrainMovementProperties(3),
                new TerrainCombatProperties(4),
                new TerrainEconomicProperties(4)
            ),
            self::WATER => new TerrainProperties(
                new TerrainVisualProperties('Water', 0x4169E1),
                new TerrainMovementProperties(0),
                new TerrainCombatProperties(0),
                new TerrainEconomicProperties(1)
            ),
            self::DESERT => new TerrainProperties(
                new TerrainVisualProperties('Desert', 0xF4A460),
                new TerrainMovementProperties(2),
                new TerrainCombatProperties(1),
                new TerrainEconomicProperties(1)
            ),
            self::SWAMP => new TerrainProperties(
                new TerrainVisualProperties('Swamp', 0x556B2F),
                new TerrainMovementProperties(3),
                new TerrainCombatProperties(2),
                new TerrainEconomicProperties(2)
            )
        };
    }
}
