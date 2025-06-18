<?php

namespace App\Application\Technology\Command;

use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Technology\ValueObject\TechnologyId;

final readonly class DiscoverTechnologyCommand
{
    public function __construct(
        public PlayerId     $playerId,
        public TechnologyId $technologyId,
        public Timestamp    $discoveredAt
    )
    {
    }
}
