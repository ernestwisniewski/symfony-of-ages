<?php

namespace App\UI\Technology\ViewModel;
final class TechnologyTreeView
{
    public string $playerId;
    public string $gameId;
    public array $unlockedTechnologies = [];
    public array $availableTechnologies = [];
    public int $sciencePoints = 0;
}
