<?php

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Application\Api\State\TechnologyStateProcessor;
use App\Application\Api\State\TechnologyStateProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Technology',
    operations: [
        new Get(
            uriTemplate: '/technologies/{technologyId}',
            normalizationContext: ['groups' => ['technology:read']],
            security: "is_granted('ROLE_USER')",
            provider: TechnologyStateProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/technologies',
            normalizationContext: ['groups' => ['technology:read']],
            security: "is_granted('ROLE_USER')",
            provider: TechnologyStateProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/players/{playerId}/technologies',
            uriVariables: [
                'playerId' => new Link(fromClass: GameResource::class)
            ],
            normalizationContext: ['groups' => ['technology:read']],
            security: "is_granted('ROLE_USER')",
            provider: TechnologyStateProvider::class
        ),
        new Post(
            uriTemplate: '/players/{playerId}/technologies/{technologyId}/discover',
            uriVariables: [
                'playerId' => new Link(fromClass: GameResource::class),
                'technologyId' => new Link(fromClass: TechnologyResource::class)
            ],
            status: 202,
            normalizationContext: ['groups' => ['technology:read']],
            denormalizationContext: ['groups' => ['technology:discover']],
            security: "is_granted('ROLE_USER')",
            output: false,
            processor: TechnologyStateProcessor::class
        ),
    ],
    paginationEnabled: false,
)]
final class TechnologyResource
{
    #[Groups(['technology:read'])]
    public string $id;
    #[Groups(['technology:read'])]
    public string $name;
    #[Groups(['technology:read'])]
    public string $description;
    #[Groups(['technology:read'])]
    public int $cost;
    #[Groups(['technology:read'])]
    public array $prerequisites = [];
    #[Groups(['technology:read'])]
    public array $effects = [];
    #[Groups(['technology:read'])]
    public bool $isUnlocked = false;
    #[Groups(['technology:read'])]
    public bool $isAvailable = false;
    #[Groups(['technology:discover'])]
    public string $playerId;
    #[Groups(['technology:discover'])]
    public string $gameId;
}
