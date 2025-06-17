<?php

namespace App\UI\Game\DTO;

use App\Domain\Shared\ValueObject\ValidationConstants;
use Symfony\Component\Validator\Constraints as Assert;

class GameCreateFormDTO
{
    #[Assert\NotBlank(message: 'Game name is required')]
    #[Assert\Length(
        min: ValidationConstants::MIN_GAME_NAME_LENGTH, 
        max: ValidationConstants::MAX_GAME_NAME_LENGTH, 
        minMessage: 'Game name must be at least ' . ValidationConstants::MIN_GAME_NAME_LENGTH . ' characters', 
        maxMessage: 'Game name cannot exceed ' . ValidationConstants::MAX_GAME_NAME_LENGTH . ' characters'
    )]
    public ?string $name = null;
} 