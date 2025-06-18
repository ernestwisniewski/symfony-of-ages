<?php

namespace App\UI\Visibility\ViewModel;

final class PlayerVisibilityView
{
    public function __construct(
        public string $playerId = '',
        public string $gameId = '',
        public int $x = 0,
        public int $y = 0,
        public string $state = '',
        public string $updatedAt = ''
    ) {
    }
} 