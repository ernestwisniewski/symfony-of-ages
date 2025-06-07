<?php

namespace App\Infrastructure\Unit\ReadModel\Doctrine;

use App\Infrastructure\Shared\Mapper\PositionToArrayTransformer;
use App\UI\Unit\ViewModel\UnitView;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: UnitView::class)]
#[ORM\Entity(repositoryClass: UnitViewRepository::class)]
#[ORM\Table(name: 'unit_view')]
class UnitViewEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $id;

    #[ORM\Column(type: 'string', length: 36)]
    public string $ownerId;

    #[ORM\Column(type: 'string', length: 36)]
    public string $gameId;

    #[ORM\Column(type: 'string', length: 50)]
    public string $type;

    #[Map(target: 'position', transform: PositionToArrayTransformer::class)]
    #[ORM\Column(type: 'integer')]
    public int $x;

    #[ORM\Column(type: 'integer')]
    public int $y;

    #[ORM\Column(type: 'integer')]
    public int $currentHealth;

    #[ORM\Column(type: 'integer')]
    public int $maxHealth;

    #[ORM\Column(type: 'boolean')]
    public bool $isDead = false;

    #[ORM\Column(type: 'integer')]
    public int $attackPower;

    #[ORM\Column(type: 'integer')]
    public int $defensePower;

    #[ORM\Column(type: 'integer')]
    public int $movementRange;

    public function __construct(
        string $id,
        string $ownerId,
        string $gameId,
        string $type,
        int    $x,
        int    $y,
        int    $currentHealth,
        int    $maxHealth,
        int    $attackPower,
        int    $defensePower,
        int    $movementRange
    )
    {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->gameId = $gameId;
        $this->type = $type;
        $this->x = $x;
        $this->y = $y;
        $this->currentHealth = $currentHealth;
        $this->maxHealth = $maxHealth;
        $this->attackPower = $attackPower;
        $this->defensePower = $defensePower;
        $this->movementRange = $movementRange;
    }
}
