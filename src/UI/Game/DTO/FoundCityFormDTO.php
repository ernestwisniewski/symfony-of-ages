<?php

namespace App\UI\Game\DTO;

use App\Domain\Shared\ValueObject\ValidationConstants;
use Symfony\Component\Validator\Constraints as Assert;

final class FoundCityFormDTO
{
    #[Assert\NotBlank(message: 'City name is required')]
    #[Assert\Length(
        min: ValidationConstants::MIN_CITY_NAME_LENGTH,
        max: ValidationConstants::MAX_CITY_NAME_LENGTH,
        minMessage: 'City name must be at least ' . ValidationConstants::MIN_CITY_NAME_LENGTH . ' characters',
        maxMessage: 'City name cannot exceed ' . ValidationConstants::MAX_CITY_NAME_LENGTH . ' characters'
    )]
    public ?string $cityName = null;
}
