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
final class CitySelectionPanelComponent
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public bool $isVisible = false;
    #[LiveProp(writable: true)]
    public ?array $cityData = null;

    #[LiveListener('city-panel:open')]
    public function open(#[LiveArg] array $payload): void
    {
        $this->cityData = $payload;
        $this->isVisible = true;
    }

    #[LiveListener('city-panel:close')]
    public function close(): void
    {
        $this->isVisible = false;
        $this->cityData = null;
    }

    public function getCityName(): string
    {
        return $this->cityData['name'] ?? 'Unknown City';
    }

    public function getCityOwner(): string
    {
        return $this->cityData['ownerId'] ?? 'Unknown';
    }

    public function getCityPosition(): array
    {
        return [
            'x' => $this->cityData['position']['x'] ?? 0,
            'y' => $this->cityData['position']['y'] ?? 0
        ];
    }

    public function getCityPopulation(): int
    {
        return $this->cityData['population'] ?? 0;
    }

    public function getCityProduction(): int
    {
        return $this->cityData['production'] ?? 0;
    }

    public function getCityFood(): int
    {
        return $this->cityData['food'] ?? 0;
    }

    public function getCityGold(): int
    {
        return $this->cityData['gold'] ?? 0;
    }

    public function getCityId(): string
    {
        return $this->cityData['cityId'] ?? '';
    }
}
