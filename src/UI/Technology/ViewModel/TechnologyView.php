<?php

namespace App\UI\Technology\ViewModel;
final class TechnologyView
{
    public string $id;
    public string $name;
    public string $description;
    public int $cost;
    public array $prerequisites = [];
    public array $effects = [];
    public bool $isUnlocked = false;
    public bool $isAvailable = false;
}
