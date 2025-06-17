<?php

namespace App\Application\Technology\DTO;
final readonly class TechnologyTreeDTO
{
    public function __construct(
        public string $playerId,
        public string $gameId,
        public array  $unlockedTechnologies = [],
        public array  $availableTechnologies = [],
        public int    $sciencePoints = 0
    )
    {
    }
}
