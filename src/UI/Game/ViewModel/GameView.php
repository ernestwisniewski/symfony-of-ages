<?php

namespace App\UI\Game\ViewModel;

final class GameView
{
    public string $id;
    public string $name;
    public string $status;
    public string $activePlayer;
    public int $currentTurn;
    public string $createdAt;
    public array $players = [];
    public int $userId;
    public ?string $startedAt = null;
    public ?string $currentTurnAt = null;
}
