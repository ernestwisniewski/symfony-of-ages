<?php

namespace App\Infrastructure\Diplomacy\ReadModel;

use App\Application\Diplomacy\Query\GetDiplomacyByGameQuery;
use App\Application\Diplomacy\Query\GetDiplomacyStatusQuery;
use App\Domain\Diplomacy\DiplomacyAgreement;
use App\Domain\Diplomacy\Event\DiplomacyAccepted;
use App\Domain\Diplomacy\Event\DiplomacyDeclined;
use App\Domain\Diplomacy\Event\DiplomacyEnded;
use App\Domain\Diplomacy\Event\DiplomacyProposed;
use App\Infrastructure\Diplomacy\ReadModel\Doctrine\DiplomacyViewEntity;
use App\Infrastructure\Diplomacy\ReadModel\Doctrine\DiplomacyViewRepository;
use App\UI\Diplomacy\ViewModel\DiplomacyView;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

#[Projection("diplomacy_view", DiplomacyAgreement::class)]
final readonly class DiplomacyProjection
{
    public function __construct(
        private EntityManagerInterface  $entityManager,
        private DiplomacyViewRepository $diplomacyViewRepository,
        private ObjectMapperInterface   $objectMapper
    )
    {
    }

    #[QueryHandler]
    public function getDiplomacyStatus(GetDiplomacyStatusQuery $query): array
    {
        $diplomacies = $this->diplomacyViewRepository->findByPlayerAndGame(
            (string)$query->playerId,
            (string)$query->gameId
        );
        return array_map(
            fn(DiplomacyViewEntity $entity) => $this->objectMapper->map($entity, DiplomacyView::class),
            $diplomacies
        );
    }

    #[QueryHandler]
    public function getDiplomacyByGame(GetDiplomacyByGameQuery $query): array
    {
        $diplomacies = $this->diplomacyViewRepository->findByGameId((string)$query->gameId);
        return array_map(
            fn(DiplomacyViewEntity $entity) => $this->objectMapper->map($entity, DiplomacyView::class),
            $diplomacies
        );
    }

    #[EventHandler]
    public function applyDiplomacyProposed(DiplomacyProposed $event): void
    {
        $diplomacyView = new DiplomacyViewEntity(
            $event->diplomacyId,
            $event->initiatorId,
            $event->targetId,
            $event->gameId,
            $event->agreementType,
            'proposed',
            DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $event->proposedAt)
        );
        $this->entityManager->persist($diplomacyView);
        $this->entityManager->flush();
    }

    #[EventHandler]
    public function applyDiplomacyAccepted(DiplomacyAccepted $event): void
    {
        $diplomacyView = $this->diplomacyViewRepository->find($event->diplomacyId);
        if ($diplomacyView) {
            $diplomacyView->status = 'accepted';
            $diplomacyView->acceptedAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $event->acceptedAt);
            $this->entityManager->flush();
        }
    }

    #[EventHandler]
    public function applyDiplomacyDeclined(DiplomacyDeclined $event): void
    {
        $diplomacyView = $this->diplomacyViewRepository->find($event->diplomacyId);
        if ($diplomacyView) {
            $diplomacyView->status = 'declined';
            $diplomacyView->declinedAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $event->declinedAt);
            $this->entityManager->flush();
        }
    }

    #[EventHandler]
    public function applyDiplomacyEnded(DiplomacyEnded $event): void
    {
        $diplomacyView = $this->diplomacyViewRepository->find($event->diplomacyId);
        if ($diplomacyView) {
            $diplomacyView->status = 'ended';
            $diplomacyView->endedAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $event->endedAt);
            $this->entityManager->flush();
        }
    }
}
