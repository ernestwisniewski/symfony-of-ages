<?php

declare(strict_types=1);

namespace App\UI\Game\LiveComponent;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class SelectionPanelComponent
{
    use DefaultActionTrait;

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

    public function getPartialTemplate(): string
    {
        return match ($this->template) {
            'city' => 'game/partials/_city_panel.html.twig',
            'unit' => 'game/partials/_unit_panel.html.twig',
            'hex', 'tile' => 'game/partials/_hex_panel.html.twig',
            default => 'game/partials/_default_panel.html.twig',
        };
    }

    /**
     * Get formatted payload data for templates
     */
    public function getPayload(): array
    {
        return $this->payload ?? [];
    }

    /**
     * Get specific payload value with fallback
     */
    public function getPayloadValue(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }
}
