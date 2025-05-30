<?php

namespace App\Domain\Map\ValueObject;

/**
 * TerrainEconomicProperties represents economic terrain characteristics
 *
 * Value Object containing resource generation and economic value information.
 * Used by economic systems and resource management.
 */
readonly class TerrainEconomicProperties
{
    public function __construct(
        private int $resourceYield
    ) {
        if ($resourceYield < 0) {
            throw new \InvalidArgumentException('Resource yield cannot be negative');
        }
    }

    public function getResourceYield(): int
    {
        return $this->resourceYield;
    }

    public function isResourceRich(): bool
    {
        return $this->resourceYield >= 3;
    }

    public function hasModerateResources(): bool
    {
        return $this->resourceYield === 2;
    }

    public function isPoorInResources(): bool
    {
        return $this->resourceYield <= 1;
    }

    public function hasNoResources(): bool
    {
        return $this->resourceYield === 0;
    }

    public function isHighValue(): bool
    {
        return $this->resourceYield >= 4;
    }

    public function toArray(): array
    {
        return [
            'resourceYield' => $this->resourceYield,
            'economicValue' => $this->getEconomicValueLevel(),
            'worthExploiting' => $this->isResourceRich()
        ];
    }

    private function getEconomicValueLevel(): string
    {
        return match (true) {
            $this->hasNoResources() => 'worthless',
            $this->resourceYield === 1 => 'poor',
            $this->hasModerateResources() => 'moderate',
            $this->resourceYield === 3 => 'good',
            $this->isHighValue() => 'excellent',
            default => 'unknown'
        };
    }
} 