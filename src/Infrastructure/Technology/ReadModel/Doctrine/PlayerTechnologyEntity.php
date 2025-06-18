<?php

namespace App\Infrastructure\Technology\ReadModel\Doctrine;

use App\UI\Technology\ViewModel\PlayerTechnologyView;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: PlayerTechnologyView::class)]
#[ORM\Entity(repositoryClass: PlayerTechnologyRepository::class)]
#[ORM\Table(name: 'player_technology')]
class PlayerTechnologyEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $playerId;
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $technologyId;
    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $discoveredAt;

    public function __construct(
        string $playerId,
        string $technologyId,
        string $discoveredAt
    )
    {
        $this->playerId = $playerId;
        $this->technologyId = $technologyId;
        $this->discoveredAt = new DateTimeImmutable($discoveredAt);
    }
}
