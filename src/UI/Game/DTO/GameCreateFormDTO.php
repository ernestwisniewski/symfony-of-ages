<?php

namespace App\UI\Game\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class GameCreateFormDTO
{
    #[Assert\NotBlank(message: 'Game name is required')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'Game name must be at least 3 characters', maxMessage: 'Game name cannot exceed 50 characters')]
    public ?string $name = null;
} 