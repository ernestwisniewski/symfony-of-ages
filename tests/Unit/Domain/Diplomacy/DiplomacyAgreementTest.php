<?php

namespace Tests\Unit\Domain\Diplomacy;

use App\Application\Diplomacy\Command\AcceptDiplomacyCommand;
use App\Application\Diplomacy\Command\DeclineDiplomacyCommand;
use App\Application\Diplomacy\Command\EndDiplomacyCommand;
use App\Application\Diplomacy\Command\ProposeDiplomacyCommand;
use App\Domain\Diplomacy\DiplomacyAgreement;
use App\Domain\Diplomacy\Event\DiplomacyAccepted;
use App\Domain\Diplomacy\Event\DiplomacyDeclined;
use App\Domain\Diplomacy\Event\DiplomacyEnded;
use App\Domain\Diplomacy\Event\DiplomacyProposed;
use App\Domain\Diplomacy\ValueObject\AgreementType;
use App\Domain\Diplomacy\ValueObject\DiplomacyId;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class DiplomacyAgreementTest extends TestCase
{
    private DiplomacyId $diplomacyId;
    private PlayerId $initiatorId;
    private PlayerId $targetId;
    private GameId $gameId;
    private AgreementType $agreementType;
    private Timestamp $proposedAt;

    protected function setUp(): void
    {
        $this->diplomacyId = new DiplomacyId(Uuid::v4()->toRfc4122());
        $this->initiatorId = new PlayerId(Uuid::v4()->toRfc4122());
        $this->targetId = new PlayerId(Uuid::v4()->toRfc4122());
        $this->gameId = new GameId(Uuid::v4()->toRfc4122());
        $this->agreementType = AgreementType::ALLIANCE;
        $this->proposedAt = Timestamp::now();
    }

    public function testProposeDiplomacy(): void
    {
        $command = new ProposeDiplomacyCommand(
            $this->diplomacyId,
            $this->initiatorId,
            $this->targetId,
            $this->gameId,
            $this->agreementType,
            $this->proposedAt
        );

        $events = DiplomacyAgreement::propose($command);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(DiplomacyProposed::class, $events[0]);

        $event = $events[0];
        $this->assertEquals((string)$this->diplomacyId, $event->diplomacyId);
        $this->assertEquals((string)$this->initiatorId, $event->initiatorId);
        $this->assertEquals((string)$this->targetId, $event->targetId);
        $this->assertEquals((string)$this->gameId, $event->gameId);
        $this->assertEquals($this->agreementType->value, $event->agreementType);
        $this->assertEquals($this->proposedAt->format(), $event->proposedAt);
    }

    public function testAcceptDiplomacy(): void
    {
        $acceptedBy = new PlayerId(Uuid::v4()->toRfc4122());
        $acceptedAt = Timestamp::now();

        $command = new AcceptDiplomacyCommand(
            $this->diplomacyId,
            $acceptedBy,
            $acceptedAt
        );

        $diplomacy = $this->createDiplomacyInProposedState();
        $events = $diplomacy->accept($command);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(DiplomacyAccepted::class, $events[0]);

        $event = $events[0];
        $this->assertEquals((string)$this->diplomacyId, $event->diplomacyId);
        $this->assertEquals((string)$acceptedBy, $event->acceptedBy);
        $this->assertEquals($acceptedAt->format(), $event->acceptedAt);
    }

    public function testDeclineDiplomacy(): void
    {
        $declinedBy = new PlayerId(Uuid::v4()->toRfc4122());
        $declinedAt = Timestamp::now();

        $command = new DeclineDiplomacyCommand(
            $this->diplomacyId,
            $declinedBy,
            $declinedAt
        );

        $diplomacy = $this->createDiplomacyInProposedState();
        $events = $diplomacy->decline($command);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(DiplomacyDeclined::class, $events[0]);

        $event = $events[0];
        $this->assertEquals((string)$this->diplomacyId, $event->diplomacyId);
        $this->assertEquals((string)$declinedBy, $event->declinedBy);
        $this->assertEquals($declinedAt->format(), $event->declinedAt);
    }

    public function testEndDiplomacy(): void
    {
        $endedBy = new PlayerId(Uuid::v4()->toRfc4122());
        $endedAt = Timestamp::now();

        $command = new EndDiplomacyCommand(
            $this->diplomacyId,
            $endedBy,
            $endedAt
        );

        $diplomacy = $this->createDiplomacyInAcceptedState();
        $events = $diplomacy->end($command);

        $this->assertCount(1, $events);
        $this->assertInstanceOf(DiplomacyEnded::class, $events[0]);

        $event = $events[0];
        $this->assertEquals((string)$this->diplomacyId, $event->diplomacyId);
        $this->assertEquals((string)$endedBy, $event->endedBy);
        $this->assertEquals($endedAt->format(), $event->endedAt);
    }

    private function createDiplomacyInProposedState(): DiplomacyAgreement
    {
        $diplomacy = new DiplomacyAgreement();

        $proposedEvent = new DiplomacyProposed(
            (string)$this->diplomacyId,
            (string)$this->initiatorId,
            (string)$this->targetId,
            (string)$this->gameId,
            $this->agreementType->value,
            $this->proposedAt->format()
        );

        $diplomacy->whenDiplomacyProposed($proposedEvent);

        return $diplomacy;
    }

    private function createDiplomacyInAcceptedState(): DiplomacyAgreement
    {
        $diplomacy = $this->createDiplomacyInProposedState();

        $acceptedEvent = new DiplomacyAccepted(
            (string)$this->diplomacyId,
            (string)$this->targetId,
            Timestamp::now()->format()
        );

        $diplomacy->whenDiplomacyAccepted($acceptedEvent);

        return $diplomacy;
    }
}
