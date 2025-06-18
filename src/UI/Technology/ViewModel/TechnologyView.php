<?php

namespace App\UI\Technology\ViewModel;
final class TechnologyView
{
    public string $playerId;
    public string $gameId;
    public array $unlockedTechnologies = [];
    public array $availableTechnologies = [];
    public int $sciencePoints = 0;
    public string $id;
    public string $name;
    public string $description;
    public int $cost;
    public array $prerequisites = [];
    public array $effects = [];
    public bool $isUnlocked = false;
    public bool $isAvailable = false;
}
