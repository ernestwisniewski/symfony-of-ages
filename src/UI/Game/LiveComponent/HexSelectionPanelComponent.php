<?php

declare(strict_types=1);

namespace App\UI\Game\LiveComponent;

use App\Domain\Map\ValueObject\TerrainType;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class HexSelectionPanelComponent
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public bool $isVisible = false;

    #[LiveProp(writable: true)]
    public ?array $hexData = null;

    #[LiveListener('panel:hex:open')]
    public function open(#[LiveArg] array $payload): void
    {
        $this->hexData = $payload;
        $this->isVisible = true;
    }

    #[LiveListener('panel:close')]
    public function close(): void
    {
        $this->isVisible = false;
        $this->hexData = null;
    }

    public function getTerrainName(): string
    {
        return $this->hexData['terrainName'] ?? 'Unknown Terrain';
    }

    public function getTerrainType(): string
    {
        return $this->hexData['terrainType'] ?? TerrainType::PLAINS->value;
    }

    public function getPosition(): array
    {
        return [
            'row' => $this->hexData['position']['row'] ?? 0,
            'col' => $this->hexData['position']['col'] ?? 0
        ];
    }

    public function getMovementCost(): int
    {
        return $this->hexData['movementCost'] ?? 1;
    }

    public function getDefenseBonus(): int
    {
        return $this->hexData['defense'] ?? 0;
    }

    public function getResources(): int
    {
        return $this->hexData['resources'] ?? 0;
    }

    public function getCoordinates(): string
    {
        return $this->hexData['coordinates'] ?? '(0, 0)';
    }

    public function getProperties(): array
    {
        return $this->hexData['properties'] ?? [];
    }

    public function hasResource(string $resourceType): bool
    {
        $properties = $this->getProperties();
        return isset($properties[$resourceType]) && $properties[$resourceType] > 0;
    }

    public function getResourceAmount(string $resourceType): int
    {
        $properties = $this->getProperties();
        return $properties[$resourceType] ?? 0;
    }
}
