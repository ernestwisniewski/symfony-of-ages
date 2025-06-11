<?php

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Application\Api\State\CityStateProcessor;
use App\Application\Api\State\CityStateProvider;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'City',
    operations: [
        new Get(
            uriTemplate: '/cities/{cityId}',
            normalizationContext: ['groups' => ['city:read']],
            security: "is_granted('ROLE_USER')",
            provider: CityStateProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/games/{gameId}/cities',
            uriVariables: [
                'gameId' => new Link(fromClass: GameResource::class)
            ],
            normalizationContext: ['groups' => ['city:read']],
            security: "is_granted('ROLE_USER')",
            provider: CityStateProvider::class
        ),
        new Post(
            uriTemplate: '/games/{gameId}/cities',
            uriVariables: [
                'gameId' => new Link(fromClass: GameResource::class)
            ],
            status: 201,
            normalizationContext: ['groups' => ['city:read']],
            denormalizationContext: ['groups' => ['city:create']],
            validationContext: ['groups' => ['city:create']],
            security: "is_granted('ROLE_USER')",
            output: false,
            processor: CityStateProcessor::class
        ),
    ],
    paginationEnabled: false,
)]
final class CityResource
{
    #[Groups(['city:read'])]
    #[ApiProperty(identifier: true)]
    public ?string $cityId = null;

    #[Groups(['city:read'])]
    public ?string $ownerId = null;

    #[Groups(['city:read'])]
    public ?string $gameId = null;

    #[Groups(['city:read', 'city:create'])]
    #[Assert\NotBlank(message: 'City name is required', groups: ['city:create'])]
    #[Assert\Length(min: 2, max: 30, groups: ['city:create'])]
    public ?string $name = null;

    #[Groups(['city:read'])]
    public ?array $position = null;

    #[Groups(['city:create'])]
    #[Assert\NotBlank(message: 'Player ID is required', groups: ['city:create'])]
    #[Assert\Uuid(groups: ['city:create'])]
    #[Map(if: false)]
    public ?string $playerId = null;

    #[Map(if: false)]
    #[Groups(['city:create'])]
    #[Assert\NotNull(message: 'X position is required', groups: ['city:create'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['city:create'])]
    public ?int $x = null;

    #[Map(if: false)]
    #[Groups(['city:create'])]
    #[Assert\NotNull(message: 'Y position is required', groups: ['city:create'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['city:create'])]
    public ?int $y = null;
}
