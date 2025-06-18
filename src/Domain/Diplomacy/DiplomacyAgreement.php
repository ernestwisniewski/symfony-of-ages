<?php

namespace App\Domain\Diplomacy;

use App\Application\Diplomacy\Command\AcceptDiplomacyCommand;
use App\Application\Diplomacy\Command\DeclineDiplomacyCommand;
use App\Application\Diplomacy\Command\EndDiplomacyCommand;
use App\Application\Diplomacy\Command\ProposeDiplomacyCommand;
use App\Domain\Diplomacy\Event\DiplomacyAccepted;
use App\Domain\Diplomacy\Event\DiplomacyDeclined;
use App\Domain\Diplomacy\Event\DiplomacyEnded;
use App\Domain\Diplomacy\Event\DiplomacyProposed;
use App\Domain\Diplomacy\Policy\DiplomacyPolicy;
use App\Domain\Diplomacy\ValueObject\AgreementStatus;
use App\Domain\Diplomacy\ValueObject\AgreementType;
use App\Domain\Diplomacy\ValueObject\DiplomacyId;
use App\Domain\Diplomacy\ValueObject\PlayerRelation;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
final class DiplomacyAgreement
{
    use WithAggregateVersioning;

    #[Identifier]
    private DiplomacyId $diplomacyId;
    private PlayerRelation $relation;
    private GameId $gameId;
    private AgreementType $type;
    private AgreementStatus $status;
    private Timestamp $proposedAt;
    private ?Timestamp $acceptedAt = null;
    private ?Timestamp $declinedAt = null;
    private ?Timestamp $endedAt = null;

    #[CommandHandler]
    public static function propose(ProposeDiplomacyCommand $command, DiplomacyPolicy $policy): array
    {
        if (!$policy->canPropose($command->initiatorId, $command->targetId, $command->agreementType)) {
            throw new \InvalidArgumentException('Cannot propose diplomacy to yourself');
        }
        
        return [
            new DiplomacyProposed(
                (string)$command->diplomacyId,
                (string)$command->initiatorId,
                (string)$command->targetId,
                (string)$command->gameId,
                $command->agreementType->value,
                $command->proposedAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function accept(AcceptDiplomacyCommand $command, DiplomacyPolicy $policy): array
    {
        if (!$policy->canAccept($command->acceptedBy, $this->relation)) {
            throw new \InvalidArgumentException('Only the target player can accept this diplomacy proposal');
        }
        
        if ($this->status !== AgreementStatus::PROPOSED) {
            throw new \InvalidArgumentException('Can only accept proposed diplomacy agreements');
        }
        
        return [
            new DiplomacyAccepted(
                (string)$this->diplomacyId,
                (string)$command->acceptedBy,
                $command->acceptedAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function decline(DeclineDiplomacyCommand $command, DiplomacyPolicy $policy): array
    {
        if (!$policy->canDecline($command->declinedBy, $this->relation)) {
            throw new \InvalidArgumentException('Only the target player can decline this diplomacy proposal');
        }
        
        if ($this->status !== AgreementStatus::PROPOSED) {
            throw new \InvalidArgumentException('Can only decline proposed diplomacy agreements');
        }
        
        return [
            new DiplomacyDeclined(
                (string)$this->diplomacyId,
                (string)$command->declinedBy,
                $command->declinedAt->format()
            )
        ];
    }

    #[CommandHandler]
    public function end(EndDiplomacyCommand $command, DiplomacyPolicy $policy): array
    {
        if (!$policy->canEnd($command->endedBy, $this->relation)) {
            throw new \InvalidArgumentException('Only involved players can end this diplomacy agreement');
        }
        
        if ($this->status !== AgreementStatus::ACCEPTED) {
            throw new \InvalidArgumentException('Can only end accepted diplomacy agreements');
        }
        
        return [
            new DiplomacyEnded(
                (string)$this->diplomacyId,
                (string)$command->endedBy,
                $command->endedAt->format()
            )
        ];
    }

    #[EventSourcingHandler]
    public function whenDiplomacyProposed(DiplomacyProposed $event): void
    {
        $this->diplomacyId = new DiplomacyId($event->diplomacyId);
        $this->relation = new PlayerRelation(
            new PlayerId($event->initiatorId),
            new PlayerId($event->targetId)
        );
        $this->gameId = new GameId($event->gameId);
        $this->type = AgreementType::from($event->agreementType);
        $this->status = AgreementStatus::PROPOSED;
        $this->proposedAt = Timestamp::fromString($event->proposedAt);
    }

    #[EventSourcingHandler]
    public function whenDiplomacyAccepted(DiplomacyAccepted $event): void
    {
        $this->status = AgreementStatus::ACCEPTED;
        $this->acceptedAt = Timestamp::fromString($event->acceptedAt);
    }

    #[EventSourcingHandler]
    public function whenDiplomacyDeclined(DiplomacyDeclined $event): void
    {
        $this->status = AgreementStatus::DECLINED;
        $this->declinedAt = Timestamp::fromString($event->declinedAt);
    }

    #[EventSourcingHandler]
    public function whenDiplomacyEnded(DiplomacyEnded $event): void
    {
        $this->status = AgreementStatus::ENDED;
        $this->endedAt = Timestamp::fromString($event->endedAt);
    }

    public function getId(): DiplomacyId
    {
        return $this->diplomacyId;
    }

    public function getStatus(): AgreementStatus
    {
        return $this->status;
    }

    public function getType(): AgreementType
    {
        return $this->type;
    }

    public function getRelation(): PlayerRelation
    {
        return $this->relation;
    }

    public function getGameId(): GameId
    {
        return $this->gameId;
    }

    public function getProposedAt(): Timestamp
    {
        return $this->proposedAt;
    }

    public function getAcceptedAt(): ?Timestamp
    {
        return $this->acceptedAt;
    }

    public function getDeclinedAt(): ?Timestamp
    {
        return $this->declinedAt;
    }

    public function getEndedAt(): ?Timestamp
    {
        return $this->endedAt;
    }
}
