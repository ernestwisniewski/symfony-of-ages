<?php

namespace App\UI\Game\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class FoundCityFormDTO
{
    // @todo const MAXCITYNAMELENGHT
    #[Assert\NotBlank(message: 'City name is required')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'City name must be at least 3 characters', maxMessage: 'City name cannot exceed 50 characters')]
    public ?string $cityName = null;
}
