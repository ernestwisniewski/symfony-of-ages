<?php

namespace App\Application\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Application\Api\State\UnitStateProcessor;
use App\Application\Api\State\UnitStateProvider;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Unit',
    operations: [
        new Get(
            uriTemplate: '/units/{unitId}',
            normalizationContext: ['groups' => ['unit:read']],
            provider: UnitStateProvider::class,
        ),
        new GetCollection(
            uriTemplate: '/games/{gameId}/units',
            uriVariables: [
                'gameId' => new Link(fromClass: GameResource::class)
            ],
            normalizationContext: ['groups' => ['unit:read']],
            provider: UnitStateProvider::class,
        ),
        new Post(
            uriTemplate: '/games/{gameId}/units',
            uriVariables: [
                'gameId' => new Link(fromClass: GameResource::class)
            ],
            status: 202,
            normalizationContext: ['groups' => ['unit:read']],
            denormalizationContext: ['groups' => ['unit:create']],
            validationContext: ['groups' => ['unit:create']],
            output: false,
            processor: UnitStateProcessor::class,
        ),
        new Patch(
            uriTemplate: '/units/{unitId}/move',
            status: 202,
            normalizationContext: ['groups' => ['unit:read']],
            denormalizationContext: ['groups' => ['unit:move']],
            validationContext: ['groups' => ['unit:move']],
            output: false,
            processor: UnitStateProcessor::class,
        ),
        new Post(
            uriTemplate: '/units/{unitId}/attack',
            status: 202,
            normalizationContext: ['groups' => ['unit:read']],
            denormalizationContext: ['groups' => ['unit:attack']],
            validationContext: ['groups' => ['unit:attack']],
            output: false,
            processor: UnitStateProcessor::class,
        ),
    ],
    paginationEnabled: false,
)]
final class UnitResource
{
    #[Groups(['unit:read'])]
    #[Map(source: 'id')]
    #[ApiProperty(identifier: true)]
    public ?string $unitId = null;

    #[Groups(['unit:read'])]
    public ?string $ownerId = null;

    #[Groups(['unit:read'])]
    public ?string $gameId = null;

    #[Groups(['unit:read'])]
    public ?string $type = null;

    #[Groups(['unit:read'])]
    public ?array $position = null;

    #[Groups(['unit:read'])]
    public ?int $currentHealth = null;

    #[Groups(['unit:read'])]
    public ?int $maxHealth = null;

    #[Groups(['unit:read'])]
    public ?bool $isDead = null;

    #[Groups(['unit:read'])]
    public ?int $attackPower = null;

    #[Groups(['unit:read'])]
    public ?int $defensePower = null;

    #[Groups(['unit:read'])]
    public ?int $movementRange = null;


    #[Groups(['unit:create'])]
    #[Assert\NotBlank(message: 'Player ID is required', groups: ['unit:create'])]
    #[Assert\Uuid(groups: ['unit:create'])]
    public ?string $playerId = null;

    #[Groups(['unit:create'])]
    #[Assert\NotBlank(message: 'Unit type is required', groups: ['unit:create'])]
    #[Assert\Choice(choices: ['warrior', 'archer', 'cavalry'], groups: ['unit:create'])]
    public ?string $unitType = null;

    #[Groups(['unit:create'])]
    #[Assert\NotNull(message: 'X position is required', groups: ['unit:create'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['unit:create'])]
    public ?int $x = null;

    #[Groups(['unit:create'])]
    #[Assert\NotNull(message: 'Y position is required', groups: ['unit:create'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['unit:create'])]
    public ?int $y = null;

    #[Groups(['unit:move'])]
    #[Assert\NotNull(message: 'Target X position is required', groups: ['unit:move'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['unit:move'])]
    public ?int $toX = null;

    #[Groups(['unit:move'])]
    #[Assert\NotNull(message: 'Target Y position is required', groups: ['unit:move'])]
    #[Assert\GreaterThanOrEqual(0, groups: ['unit:move'])]
    public ?int $toY = null;

    #[Groups(['unit:attack'])]
    #[Assert\NotBlank(message: 'Target unit ID is required', groups: ['unit:attack'])]
    #[Assert\Uuid(groups: ['unit:attack'])]
    public ?string $targetUnitId = null;
}
