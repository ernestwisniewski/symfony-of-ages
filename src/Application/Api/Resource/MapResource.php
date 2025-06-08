<?php

namespace App\Application\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
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
            provider: MapStateProvider::class,
        ),
        new Post(
            uriTemplate: '/games/{gameId}/generate-map',
            uriVariables: [
                'gameId' => new Link(fromClass: GameResource::class)
            ],
            normalizationContext: ['groups' => ['map:read']],
            denormalizationContext: ['groups' => ['map:generate']],
            validationContext: ['groups' => ['map:generate']],
            processor: MapStateProcessor::class,
        ),
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

    #[Groups(['map:generate'])]
    #[Assert\NotNull(message: 'Map width is required', groups: ['map:generate'])]
    #[Assert\Range(min: 10, max: 100, groups: ['map:generate'])]
    public ?int $mapWidth = null;

    #[Groups(['map:generate'])]
    #[Assert\NotNull(message: 'Map height is required', groups: ['map:generate'])]
    #[Assert\Range(min: 10, max: 100, groups: ['map:generate'])]
    public ?int $mapHeight = null;
}
