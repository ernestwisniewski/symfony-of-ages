<?php

namespace App\UI\Unit\ViewModel;

final class UnitView
{
    public string $id;
    public string $ownerId;
    public string $gameId;
    public string $type;
    public array $position;
    public int $currentHealth;
    public int $maxHealth;
    public bool $isDead;
    public int $attackPower;
    public int $defensePower;
    public int $movementRange;
} 