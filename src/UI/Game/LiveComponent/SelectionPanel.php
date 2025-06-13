<?php

declare(strict_types=1);

namespace App\UI\Game\LiveComponent;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('SelectionPanel')]
final class SelectionPanel
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?array $selectedHex = null;

    #[LiveProp(writable: true)]
    public ?array $selectedUnit = null;

    #[LiveProp(writable: true)]
    public bool $isVisible = false;

    public function mount(): void
    {
        $this->selectedHex = null;
        $this->selectedUnit = null;
        $this->isVisible = false;
    }

    public function selectHex(array $hexData): void
    {
        $this->selectedHex = $hexData;
        $this->selectedUnit = null;
        $this->isVisible = true;
    }

    public function selectUnit(array $unitData): void
    {
        $this->selectedUnit = $unitData;
        $this->selectedHex = null;
        $this->isVisible = true;
    }

    public function clearSelection(): void
    {
        $this->selectedHex = null;
        $this->selectedUnit = null;
        $this->isVisible = false;
    }

    public function getSelectedObject(): ?array
    {
        if ($this->selectedHex) {
            return [
                'type' => 'hex',
                'data' => $this->selectedHex,
                'displayName' => $this->getHexDisplayName($this->selectedHex),
                'position' => $this->selectedHex['position'] ?? ['row' => 0, 'col' => 0],
                'details' => $this->getHexDetails($this->selectedHex)
            ];
        }

        if ($this->selectedUnit) {
            return [
                'type' => 'unit',
                'data' => $this->selectedUnit,
                'displayName' => $this->getUnitDisplayName($this->selectedUnit),
                'position' => $this->selectedUnit['position'] ?? ['row' => 0, 'col' => 0],
                'details' => $this->getUnitDetails($this->selectedUnit)
            ];
        }

        return null;
    }

    private function getHexDisplayName(array $hexData): string
    {
        $terrainName = $hexData['terrainName'] ?? 'Unknown terrain';
        return "Field: {$terrainName}";
    }

    private function getHexDetails(array $hexData): array
    {
        return [
            'Terrain' => $hexData['terrainName'] ?? 'Unknown',
            'Movement cost' => $hexData['movementCost'] ?? 0,
            'Defense' => $hexData['defense'] ?? 0,
            'Resources' => $hexData['resources'] ?? 0
        ];
    }

    private function getUnitDisplayName(array $unitData): string
    {
        $type = $unitData['type'] ?? 'Unknown unit';
        $owner = $unitData['ownerId'] ?? 'Unknown player';
        return "{$type} ({$owner})";
    }

    private function getUnitDetails(array $unitData): array
    {
        $position = $unitData['position'] ?? ['x' => 0, 'y' => 0];
        return [
            'Type' => $unitData['type'] ?? 'Unknown',
            'Owner' => $unitData['ownerId'] ?? 'Unknown',
            'Movement points' => $unitData['movementRange'] ?? 0,
            'Position' => "({$position['x']}, {$position['y']})"
        ];
    }
} 