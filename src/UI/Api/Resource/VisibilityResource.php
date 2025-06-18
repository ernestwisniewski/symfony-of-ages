<?php

namespace App\UI\Api\Resource;

use App\Application\Api\State\VisibilityStateProvider;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Visibility',
    operations: [
        new Get(
            uriTemplate: '/players/{playerId}/visibility',
            uriVariables: [
                'playerId' => new Link(fromClass: GameResource::class)
            ],
            normalizationContext: ['groups' => ['visibility:read']],
            security: "is_granted('ROLE_USER')",
            provider: VisibilityStateProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/games/{gameId}/visibility',
            uriVariables: [
                'gameId' => new Link(fromClass: GameResource::class)
            ],
            normalizationContext: ['groups' => ['visibility:read']],
            security: "is_granted('ROLE_USER')",
            provider: VisibilityStateProvider::class
        ),
    ],
    paginationEnabled: false,
)]
final class VisibilityResource
{
    public function __construct(
        #[Groups(['visibility:read'])]
        public string $playerId = '',
        #[Groups(['visibility:read'])]
        public string $gameId = '',
        #[Groups(['visibility:read'])]
        public int $x = 0,
        #[Groups(['visibility:read'])]
        public int $y = 0,
        #[Groups(['visibility:read'])]
        public string $state = '',
        #[Groups(['visibility:read'])]
        public string $updatedAt = ''
    ) {
    }
} 