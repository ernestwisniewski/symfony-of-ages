<?php

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Application\Api\State\TurnStateProcessor;
use App\Application\Api\State\TurnStateProvider;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Turn',
    operations: [
        new Get(
            uriTemplate: '/games/{gameId}/current-turn',
            normalizationContext: ['groups' => ['turn:read']],
            security: "is_granted('ROLE_USER')",
            provider: TurnStateProcessor::class,
        ),
        new Post(
            uriTemplate: '/games/{gameId}/end-turn',
            uriVariables: [
                'gameId' => new Link(fromClass: GameResource::class)
            ],
            normalizationContext: ['groups' => ['turn:read']],
            denormalizationContext: ['groups' => ['turn:end']],
            security: "is_granted('ROLE_USER')",
            validationContext: ['groups' => ['turn:end']],
            processor: TurnStateProvider::class,
        )
    ],
    paginationEnabled: false,
)]
final class TurnResource
{
    #[Groups(['turn:read'])]
    #[ApiProperty(identifier: true)]
    public ?string $gameId = null;
    #[Groups(['turn:read'])]
    public ?string $activePlayer = null;
    #[Groups(['turn:read'])]
    public ?int $currentTurn = null;
    #[Groups(['turn:read'])]
    public ?string $turnEndedAt = null;
    #[Groups(['turn:end'])]
    #[Assert\NotBlank(message: 'Player ID is required', groups: ['turn:end'])]
    #[Assert\Uuid(groups: ['turn:end'])]
    public ?string $playerId = null;
}
