<?php

namespace App\Infrastructure\Diplomacy\ReadModel\Doctrine;

use App\UI\Diplomacy\ViewModel\DiplomacyView;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: DiplomacyView::class)]
#[ORM\Entity(repositoryClass: DiplomacyViewRepository::class)]
#[ORM\Table(name: 'diplomacy_view')]
class DiplomacyViewEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    public string $diplomacyId;
    #[ORM\Column(type: 'string', length: 36)]
    public string $initiatorId;
    #[ORM\Column(type: 'string', length: 36)]
    public string $targetId;
    #[ORM\Column(type: 'string', length: 36)]
    public string $gameId;
    #[ORM\Column(type: 'string', length: 50)]
    public string $agreementType;
    #[ORM\Column(type: 'string', length: 20)]
    public string $status;
    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $proposedAt;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $acceptedAt = null;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $declinedAt = null;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?DateTimeImmutable $endedAt = null;

    public function __construct(
        string             $diplomacyId,
        string             $initiatorId,
        string             $targetId,
        string             $gameId,
        string             $agreementType,
        string             $status,
        DateTimeImmutable  $proposedAt,
        ?DateTimeImmutable $acceptedAt = null,
        ?DateTimeImmutable $declinedAt = null,
        ?DateTimeImmutable $endedAt = null
    )
    {
        $this->diplomacyId = $diplomacyId;
        $this->initiatorId = $initiatorId;
        $this->targetId = $targetId;
        $this->gameId = $gameId;
        $this->agreementType = $agreementType;
        $this->status = $status;
        $this->proposedAt = $proposedAt;
        $this->acceptedAt = $acceptedAt;
        $this->declinedAt = $declinedAt;
        $this->endedAt = $endedAt;
    }
}
