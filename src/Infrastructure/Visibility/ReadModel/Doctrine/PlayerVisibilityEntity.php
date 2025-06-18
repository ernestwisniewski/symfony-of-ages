<?php

namespace App\Infrastructure\Visibility\ReadModel\Doctrine;

use App\UI\Visibility\ViewModel\PlayerVisibilityView;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Attribute\MapFrom;

#[Map(target: PlayerVisibilityView::class)]
#[ORM\Entity(repositoryClass: PlayerVisibilityRepository::class)]
#[ORM\Table(name: 'player_visibility')]
class PlayerVisibilityEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $playerId;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public int $x;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public int $y;

    #[ORM\Column(type: 'string', length: 20)]
    public string $state;

    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $updatedAt;

    public function __construct(
        string            $playerId,
        int               $x,
        int               $y,
        string            $state,
        DateTimeImmutable $updatedAt
    )
    {
        $this->playerId = $playerId;
        $this->x = $x;
        $this->y = $y;
        $this->state = $state;
        $this->updatedAt = $updatedAt;
    }
}
