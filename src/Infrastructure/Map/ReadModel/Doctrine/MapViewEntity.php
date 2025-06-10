<?php

namespace App\Infrastructure\Map\ReadModel\Doctrine;

use App\UI\Map\ViewModel\MapView;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: MapView::class)]
#[ORM\Entity(repositoryClass: MapViewRepository::class)]
#[ORM\Table(name: 'map_view')]
class MapViewEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $gameId;

    #[ORM\Column(type: 'integer')]
    public int $width;

    #[ORM\Column(type: 'integer')]
    public int $height;

    #[ORM\Column(type: 'json')]
    public array $tiles;

    #[ORM\Column(type: 'string', length: 30)]
    public string $generatedAt;

    public function __construct(
        string $gameId,
        int    $width,
        int    $height,
        array  $tiles,
        string $generatedAt
    )
    {
        $this->gameId = $gameId;
        $this->width = $width;
        $this->height = $height;
        $this->tiles = $tiles;
        $this->generatedAt = $generatedAt;
    }
}
