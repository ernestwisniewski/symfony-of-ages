<?php

namespace App\Domain\Map\ValueObject;

use App\Domain\Map\Exception\InvalidTerrainDataException;

/**
 * TerrainEconomicProperties encapsulates economic-related terrain characteristics
 *
 * Immutable value object that represents the resource yield and economic value
 * of different terrain types for resource management and economic calculations.
 * Uses readonly properties to ensure true immutability.
 */
class TerrainEconomicProperties
{
    public readonly int $resourceYield;

    public function __construct(int $resourceYield)
    {
        if ($resourceYield < 0) {
            throw InvalidTerrainDataException::negativeResourceYield();
        }

        $this->resourceYield = $resourceYield;
    }

    /**
     * Determines if terrain is resource-rich (yield >= 3)
     */
    public function isResourceRich(): bool
    {
        return $this->resourceYield >= 3;
    }

    /**
     * Determines if terrain has moderate resources (yield = 2)
     */
    public function hasModeratResources(): bool
    {
        return $this->resourceYield === 2;
    }

    /**
     * Determines if terrain is poor in resources (yield <= 1)
     */
    public function isPoorInResources(): bool
    {
        return $this->resourceYield <= 1;
    }

    /**
     * Determines if terrain has no resources (yield = 0)
     */
    public function hasNoResources(): bool
    {
        return $this->resourceYield === 0;
    }

    /**
     * Determines if terrain is high-value economically (yield >= 4)
     */
    public function isHighValue(): bool
    {
        return $this->resourceYield >= 4;
    }

    public function toArray(): array
    {
        return [
            'resourceYield' => $this->resourceYield,
            'isResourceRich' => $this->isResourceRich(),
            'isHighValue' => $this->isHighValue(),
            'economicValueLevel' => $this->getEconomicValueLevel()
        ];
    }

    /**
     * Gets human-readable economic value level
     */
    private function getEconomicValueLevel(): string
    {
        return match ($this->resourceYield) {
            0 => 'None',
            1 => 'Poor',
            2 => 'Moderate',
            3 => 'Rich',
            4 => 'Abundant Level 1',
            5 => 'Abundant Level 2',
            default => 'Exceptional'
        };
    }
}
