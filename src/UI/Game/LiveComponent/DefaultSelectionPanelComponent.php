<?php

declare(strict_types=1);

namespace App\UI\Game\LiveComponent;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DefaultSelectionPanelComponent
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public bool $isVisible = false;

    #[LiveListener('default-panel:open')]
    public function open(): void
    {
        $this->isVisible = true;
    }

    #[LiveListener('default-panel:close')]
    public function close(): void
    {
        $this->isVisible = false;
    }
}
