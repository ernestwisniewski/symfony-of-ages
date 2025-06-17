<?php

namespace App\Application\Technology\DTO;
final readonly class TechnologyDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public int    $cost,
        public array  $prerequisites = [],
        public array  $effects = [],
        public bool   $isUnlocked = false,
        public bool   $isAvailable = false
    )
    {
    }
}
