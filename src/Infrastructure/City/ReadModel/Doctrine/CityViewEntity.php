<?php

namespace App\Infrastructure\City\ReadModel\Doctrine;

use App\Infrastructure\Shared\Mapper\PositionToArrayTransformer;
use App\UI\City\ViewModel\CityView;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: CityView::class)]
#[ORM\Entity(repositoryClass: CityViewRepository::class)]
#[ORM\Table(name: 'city_view')]
class CityViewEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $id;

    #[ORM\Column(type: 'string', length: 36)]
    public string $ownerId;

    #[ORM\Column(type: 'string', length: 36)]
    public string $gameId;

    #[ORM\Column(type: 'string', length: 100)]
    public string $name;

    #[Map(target: 'position', transform: PositionToArrayTransformer::class)]
    #[ORM\Column(type: 'integer')]
    public int $x;

    #[ORM\Column(type: 'integer')]
    public int $y;

    public function __construct(string $id, string $ownerId, string $gameId, string $name, int $x, int $y)
    {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->gameId = $gameId;
        $this->name = $name;
        $this->x = $x;
        $this->y = $y;
    }
}
