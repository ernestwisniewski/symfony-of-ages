<?php

namespace App\Infrastructure\Player\ReadModel\Doctrine;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerUserMappingRepository::class)]
#[ORM\Table(name: 'player_user_mapping')]
class PlayerUserMappingEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    public string $id;

    #[ORM\Column(type: 'string', length: 36)]
    public string $playerId;

    #[ORM\Column(type: 'integer')]
    public int $userId;

    #[ORM\Column(type: 'string', length: 36)]
    public string $gameId;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $playerId,
        int $userId,
        string $gameId,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->playerId = $playerId;
        $this->userId = $userId;
        $this->gameId = $gameId;
        $this->createdAt = $createdAt;
    }
}
