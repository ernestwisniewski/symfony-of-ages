<?php

declare(strict_types=1);

namespace App\UI\Game\LiveComponent;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class UnitSelectionPanelComponent
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public bool $isVisible = false;

    #[LiveProp(writable: true)]
    public ?array $unitData = null;

    #[LiveListener('panel:unit:open')]
    public function open(#[LiveArg] array $payload): void
    {
        $this->unitData = $payload;
        $this->isVisible = true;
    }

    #[LiveListener('panel:close')]
    public function close(): void
    {
        $this->isVisible = false;
        $this->unitData = null;
    }

    public function getUnitType(): string
    {
        return $this->unitData['type'] ?? 'Unknown';
    }

    public function getUnitOwner(): string
    {
        return $this->unitData['ownerId'] ?? 'Unknown';
    }

    public function getUnitPosition(): array
    {
        return [
            'x' => $this->unitData['position']['x'] ?? 0,
            'y' => $this->unitData['position']['y'] ?? 0
        ];
    }

    public function getUnitHealth(): int
    {
        return $this->unitData['health'] ?? 100;
    }

    public function getUnitMovementRange(): int
    {
        return $this->unitData['movementRange'] ?? 0;
    }

    public function getUnitAttack(): int
    {
        return $this->unitData['attack'] ?? 0;
    }

    public function getUnitDefense(): int
    {
        return $this->unitData['defense'] ?? 0;
    }

    public function isUnitDead(): bool
    {
        return $this->unitData['isDead'] ?? false;
    }

    public function isSettler(): bool
    {
        return $this->getUnitType() === 'settler';
    }
}
