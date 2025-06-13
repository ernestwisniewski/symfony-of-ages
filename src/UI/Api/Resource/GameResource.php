<?php

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Application\Api\State\GameStateProcessor;
use App\Application\Api\State\GameStateProvider;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Game',
    operations: [
        new Get(
            uriTemplate: '/games/{gameId}',
            normalizationContext: ['groups' => ['game:read']],
            security: "is_granted('ROLE_USER')",
            provider: GameStateProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/games',
            normalizationContext: ['groups' => ['game:read']],
            security: "is_granted('ROLE_USER')",
            provider: GameStateProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/user/games',
            normalizationContext: ['groups' => ['game:read']],
            security: "is_granted('ROLE_USER')",
            provider: GameStateProvider::class,
        ),
        new Post(
            uriTemplate: '/games',
            status: 202,
            normalizationContext: ['groups' => ['game:read']],
            denormalizationContext: ['groups' => ['game:create']],
            security: "is_granted('ROLE_USER')",
            validationContext: ['groups' => ['game:create']],
            output: false,
            processor: GameStateProcessor::class
        ),
        new Post(
            uriTemplate: '/games/{gameId}/start',
            status: 202,
            normalizationContext: ['groups' => ['game:read']],
            denormalizationContext: ['groups' => ['game:start']],
            security: "is_granted('ROLE_USER')",
            output: false,
            processor: GameStateProcessor::class
        ),
        new Post(
            uriTemplate: '/games/{gameId}/join',
            status: 202,
            normalizationContext: ['groups' => ['game:read']],
            denormalizationContext: ['groups' => ['game:join']],
            security: "is_granted('ROLE_USER')",
            validationContext: ['groups' => ['game:join']],
            output: false,
            processor: GameStateProcessor::class,
        ),
    ],
    paginationEnabled: false,
)]
final class GameResource
{
    #[Groups(['game:read'])]
    #[Map(source: 'id')]
    #[ApiProperty(identifier: true)]
    public ?string $gameId = null;

    #[Groups(['game:read', 'game:create'])]
    #[Assert\NotBlank(message: 'Game name is required', groups: ['game:create'])]
    #[Assert\Length(min: 3, max: 50, groups: ['game:create'])]
    public ?string $name = null;

    #[Groups(['game:read'])]
    public ?string $status = null;

    #[Groups(['game:read'])]
    public ?array $players = null;

    #[Groups(['game:read'])]
    public ?int $currentTurn = null;

    #[Groups(['game:read'])]
    public ?string $activePlayer = null;

    #[Groups(['game:read'])]
    public ?string $createdAt = null;

    #[Groups(['game:read'])]
    public ?string $startedAt = null;

    #[Map(if: false)]
    #[Groups(['game:join'])]
    #[Assert\NotBlank(message: 'Player ID is required', groups: ['game:join'])]
    #[Assert\Uuid(groups: ['game:join'])]
    public ?string $playerId = null;
}
