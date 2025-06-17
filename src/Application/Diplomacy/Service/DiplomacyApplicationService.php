<?php

namespace App\Application\Diplomacy\Service;

use App\Application\Diplomacy\Command\AcceptDiplomacyCommand;
use App\Application\Diplomacy\Command\DeclineDiplomacyCommand;
use App\Application\Diplomacy\Command\EndDiplomacyCommand;
use App\Application\Diplomacy\Command\ProposeDiplomacyCommand;
use App\Application\Diplomacy\Query\GetDiplomacyByGameQuery;
use App\Application\Diplomacy\Query\GetDiplomacyStatusQuery;
use App\Domain\Diplomacy\ValueObject\AgreementType;
use App\Domain\Diplomacy\ValueObject\DiplomacyId;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Symfony\Component\Uid\Uuid;

final readonly class DiplomacyApplicationService
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus   $queryBus
    )
    {
    }

    public function proposeDiplomacy(
        PlayerId      $initiatorId,
        PlayerId      $targetId,
        GameId        $gameId,
        AgreementType $agreementType
    ): array
    {
        $diplomacyId = new DiplomacyId(Uuid::v4()->toRfc4122());
        $proposedAt = Timestamp::now();
        $command = new ProposeDiplomacyCommand(
            $diplomacyId,
            $initiatorId,
            $targetId,
            $gameId,
            $agreementType,
            $proposedAt
        );
        $this->commandBus->send($command);
        return [
            'diplomacyId' => (string)$diplomacyId,
            'status' => 'proposed',
            'proposedAt' => $proposedAt->format()
        ];
    }

    public function acceptDiplomacy(DiplomacyId $diplomacyId, PlayerId $acceptedBy): array
    {
        $acceptedAt = Timestamp::now();
        $command = new AcceptDiplomacyCommand(
            $diplomacyId,
            $acceptedBy,
            $acceptedAt
        );
        $this->commandBus->send($command);
        return [
            'diplomacyId' => (string)$diplomacyId,
            'status' => 'accepted',
            'acceptedAt' => $acceptedAt->format()
        ];
    }

    public function declineDiplomacy(DiplomacyId $diplomacyId, PlayerId $declinedBy): array
    {
        $declinedAt = Timestamp::now();
        $command = new DeclineDiplomacyCommand(
            $diplomacyId,
            $declinedBy,
            $declinedAt
        );
        $this->commandBus->send($command);
        return [
            'diplomacyId' => (string)$diplomacyId,
            'status' => 'declined',
            'declinedAt' => $declinedAt->format()
        ];
    }

    public function endDiplomacy(DiplomacyId $diplomacyId, PlayerId $endedBy): array
    {
        $endedAt = Timestamp::now();
        $command = new EndDiplomacyCommand(
            $diplomacyId,
            $endedBy,
            $endedAt
        );
        $this->commandBus->send($command);
        return [
            'diplomacyId' => (string)$diplomacyId,
            'status' => 'ended',
            'endedAt' => $endedAt->format()
        ];
    }

    public function getDiplomacyStatus(PlayerId $playerId, GameId $gameId): array
    {
        $query = new GetDiplomacyStatusQuery($playerId, $gameId);
        return $this->queryBus->send($query);
    }

    public function getDiplomacyByGame(GameId $gameId): array
    {
        $query = new GetDiplomacyByGameQuery($gameId);
        return $this->queryBus->send($query);
    }
}
