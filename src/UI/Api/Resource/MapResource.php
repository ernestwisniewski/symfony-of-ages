<?php

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Application\Api\State\MapStateProcessor;
use App\Application\Api\State\MapStateProvider;
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
    #[Assert\Range(min: 10, max: 100, groups: ['map:generate'])]
    public ?int $mapWidth = null;

    #[Map(if: false)]
    #[Groups(['map:generate'])]
    #[Assert\NotNull(message: 'Map height is required', groups: ['map:generate'])]
    #[Assert\Range(min: 10, max: 100, groups: ['map:generate'])]
    public ?int $mapHeight = null;
}
