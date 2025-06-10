<?php

namespace App\Infrastructure\Game\ReadModel\Doctrine;

use App\Infrastructure\Shared\Mapper\DateTimeToStringTransformer;
use App\UI\Game\ViewModel\GameView;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: GameView::class)]
#[ORM\Entity(repositoryClass: GameViewRepository::class)]
#[ORM\Table(name: 'game_view')]
class GameViewEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $id;

    #[ORM\Column(type: 'string', length: 120)]
    public string $name;

    #[ORM\Column(type: 'string', length: 36)]
    public string $activePlayer;

    #[ORM\Column(type: 'integer')]
    public int $currentTurn;

    #[Map(transform: [DateTimeToStringTransformer::class, 'format'])]
    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 20)]
    public string $status;

    #[ORM\Column(type: 'json')]
    public array $players;

    #[Map(transform: [DateTimeToStringTransformer::class, 'format'])]
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $startedAt = null;

    #[Map(transform: [DateTimeToStringTransformer::class, 'format'])]
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $currentTurnAt = null;


    public function __construct(
        string            $id,
        string            $name,
        string            $activePlayer,
        int               $currentTurn,
        DateTimeImmutable $createdAt,
        string            $status,
        array             $players
    )
    {
        $this->id = $id;
        $this->name = $name;
        $this->activePlayer = $activePlayer;
        $this->currentTurn = $currentTurn;
        $this->createdAt = $createdAt;
        $this->status = $status;
        $this->players = $players;
    }
}
