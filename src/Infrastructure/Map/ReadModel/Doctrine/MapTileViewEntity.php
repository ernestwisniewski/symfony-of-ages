<?php

namespace App\Infrastructure\Map\ReadModel\Doctrine;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: MapTileViewRepository::class)]
#[ORM\Table(name: 'map_view')]
class MapTileViewEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    public string $id;

    #[ORM\Column(type: 'string')]
    public string $gameId;

    #[ORM\Column(type: 'integer')]
    public int $x;

    #[ORM\Column(type: 'integer')]
    public int $y;

    #[ORM\Column(type: 'string')]
    public string $terrain;

    #[ORM\Column(type: 'boolean')]
    public bool $isOccupied = false;

    public function __construct(string $gameId, int $x, int $y, string $terrain)
    {
        $this->gameId = $gameId;
        $this->x = $x;
        $this->y = $y;
        $this->terrain = $terrain;
        $this->id = Uuid::uuid4()->toString();
    }
}
