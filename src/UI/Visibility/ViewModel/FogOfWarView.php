<?php

namespace App\UI\Visibility\ViewModel;

final class FogOfWarView
{
    public function __construct(
        public int    $x,
        public int    $y,
        public string $state,
        public bool   $isVisible = false,
        public bool   $isDiscovered = false
    )
    {
    }
}
