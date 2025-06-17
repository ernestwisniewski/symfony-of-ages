<?php

namespace App\UI\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Application\Api\State\DiplomacyStateProcessor;
use App\Application\Api\State\DiplomacyStateProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Diplomacy',
    operations: [
        new Get(
            uriTemplate: '/diplomacy/{diplomacyId}',
            normalizationContext: ['groups' => ['diplomacy:read']],
            security: "is_granted('ROLE_USER')",
            provider: DiplomacyStateProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/games/{gameId}/diplomacy',
            uriVariables: [
                'gameId' => new Link(fromClass: GameResource::class)
            ],
            normalizationContext: ['groups' => ['diplomacy:read']],
            security: "is_granted('ROLE_USER')",
            provider: DiplomacyStateProvider::class
        ),
        new GetCollection(
            uriTemplate: '/players/{playerId}/diplomacy',
            uriVariables: [
                'playerId' => new Link(fromClass: GameResource::class)
            ],
            normalizationContext: ['groups' => ['diplomacy:read']],
            security: "is_granted('ROLE_USER')",
            provider: DiplomacyStateProvider::class
        ),
        new Post(
            uriTemplate: '/games/{gameId}/diplomacy/propose',
            uriVariables: [
                'gameId' => new Link(fromClass: GameResource::class)
            ],
            status: 202,
            normalizationContext: ['groups' => ['diplomacy:read']],
            denormalizationContext: ['groups' => ['diplomacy:propose']],
            security: "is_granted('ROLE_USER')",
            validationContext: ['groups' => ['diplomacy:propose']],
            output: false,
            processor: DiplomacyStateProcessor::class
        ),
        new Post(
            uriTemplate: '/diplomacy/{diplomacyId}/accept',
            status: 202,
            normalizationContext: ['groups' => ['diplomacy:read']],
            denormalizationContext: ['groups' => ['diplomacy:accept']],
            security: "is_granted('ROLE_USER')",
            output: false,
            processor: DiplomacyStateProcessor::class
        ),
        new Post(
            uriTemplate: '/diplomacy/{diplomacyId}/decline',
            status: 202,
            normalizationContext: ['groups' => ['diplomacy:read']],
            denormalizationContext: ['groups' => ['diplomacy:decline']],
            security: "is_granted('ROLE_USER')",
            output: false,
            processor: DiplomacyStateProcessor::class
        ),
        new Post(
            uriTemplate: '/diplomacy/{diplomacyId}/end',
            status: 202,
            normalizationContext: ['groups' => ['diplomacy:read']],
            denormalizationContext: ['groups' => ['diplomacy:end']],
            security: "is_granted('ROLE_USER')",
            output: false,
            processor: DiplomacyStateProcessor::class
        ),
    ],
    paginationEnabled: false,
)]
final class DiplomacyResource
{
    public function __construct(
        #[Groups(['diplomacy:read'])]
        public string  $diplomacyId = '',
        #[Groups(['diplomacy:read', 'diplomacy:propose'])]
        public string  $initiatorId = '',
        #[Groups(['diplomacy:read', 'diplomacy:propose'])]
        public string  $targetId = '',
        #[Groups(['diplomacy:read', 'diplomacy:propose'])]
        public string  $gameId = '',
        #[Groups(['diplomacy:read', 'diplomacy:propose'])]
        public string  $agreementType = '',
        #[Groups(['diplomacy:read'])]
        public string  $status = '',
        #[Groups(['diplomacy:read'])]
        public string  $proposedAt = '',
        #[Groups(['diplomacy:read'])]
        public ?string $acceptedAt = null,
        #[Groups(['diplomacy:read'])]
        public ?string $declinedAt = null,
        #[Groups(['diplomacy:read'])]
        public ?string $endedAt = null
    )
    {
    }
}
