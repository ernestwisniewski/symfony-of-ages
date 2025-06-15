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
final class SelectionPanelComponent
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public ?array $selectedHex = null;
    #[LiveProp(writable: true)]
    public bool $isVisible = false;

    #[LiveProp(writable: true)]
    public ?string $template = null;

    #[LiveProp(writable: true)]
    public ?array $payload = null;

    #[LiveListener('open')]
    public function open(#[LiveArg] string $type, #[LiveArg] array $payload): void
    {
        $this->template = $type;
        $this->payload = $payload;
        $this->isVisible = true;
    }

    #[LiveListener('close')]
    public function close(): void
    {
        $this->isVisible = false;
        $this->template = null;
        $this->selectedHex = null;
        $this->payload = null;
    }

    #[LiveListener('found-city:success')]
    public function onCityFounded(#[LiveArg] array $data): void
    {
        $this->close();
    }

    public function getPartialTemplate(): string
    {
        return match ($this->template) {
            'city' => 'game/partials/_city_panel.html.twig',
            'unit' => 'game/partials/_unit_panel.html.twig',
            'hex', 'tile' => 'game/partials/_hex_panel.html.twig',
            default => 'game/partials/_default_panel.html.twig',
        };
    }

    public function getPayload(): array
    {
        return $this->payload ?? [];
    }

    /**
     * Get specific payload value with fallback
     * Supports nested keys using dot notation (e.g., 'position.x')
     */
    public function getPayloadValue(string $key, mixed $default = null): mixed
    {
        if (!str_contains($key, '.')) {
            return $this->payload[$key] ?? $default;
        }

        $keys = explode('.', $key);
        $value = $this->payload;

        foreach ($keys as $nestedKey) {
            if (!is_array($value) || !array_key_exists($nestedKey, $value)) {
                return $default;
            }
            $value = $value[$nestedKey];
        }

        return $value;
    }
}
