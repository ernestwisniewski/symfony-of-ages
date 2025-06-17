<?php

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Application\Api\State\MapStateProcessor;
use App\Application\Api\State\MapStateProvider;
use App\Domain\Shared\ValueObject\ValidationConstants;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Map',
    operations: [
        new Get(
            uriTemplate: '/games/{gameId}/map',
            normalizationContext: ['groups' => ['map:read']],
            security: "is_granted('ROLE_USER')",
            provider: MapStateProvider::class,
        )
    ],
    paginationEnabled: false,
)]
final class MapResource
{
    #[Groups(['map:read'])]
    public ?string $gameId = null;
    #[Groups(['map:read'])]
    public ?int $width = null;
    #[Groups(['map:read'])]
    public ?int $height = null;
    #[Groups(['map:read'])]
    public ?array $tiles = null;
    #[Groups(['map:read'])]
    public ?string $generatedAt = null;
    #[Map(if: false)]
    #[Groups(['map:generate'])]
    #[Assert\NotNull(message: 'Map width is required', groups: ['map:generate'])]
    #[Assert\Range(
        min: ValidationConstants::MIN_MAP_SIZE,
        max: ValidationConstants::MAX_MAP_SIZE,
        notInRangeMessage: 'Map width must be between ' . ValidationConstants::MIN_MAP_SIZE . ' and ' . ValidationConstants::MAX_MAP_SIZE,
        groups: ['map:generate']
    )]
    public ?int $mapWidth = null;
    #[Map(if: false)]
    #[Groups(['map:generate'])]
    #[Assert\NotNull(message: 'Map height is required', groups: ['map:generate'])]
    #[Assert\Range(
        min: ValidationConstants::MIN_MAP_SIZE,
        max: ValidationConstants::MAX_MAP_SIZE,
        notInRangeMessage: 'Map height must be between ' . ValidationConstants::MIN_MAP_SIZE . ' and ' . ValidationConstants::MAX_MAP_SIZE,
        groups: ['map:generate']
    )]
    public ?int $mapHeight = null;
}
