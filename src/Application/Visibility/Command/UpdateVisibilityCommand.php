<?php

namespace App\Application\Visibility\Command;

use App\Domain\Shared\ValueObject\Timestamp;

final readonly class UpdateVisibilityCommand
{
    public function __construct(
        public string    $playerId,
        public array     $unitPositions,
        public array     $cityPositions,
        public Timestamp $updatedAt
    )
    {
    }
}
