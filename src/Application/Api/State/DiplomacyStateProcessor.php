<?php

namespace App\Application\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Diplomacy\Service\DiplomacyApplicationService;
use App\Domain\Diplomacy\ValueObject\AgreementType;
use App\Domain\Diplomacy\ValueObject\DiplomacyId;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Infrastructure\Diplomacy\ReadModel\Doctrine\DiplomacyViewRepository;
use App\Infrastructure\Player\ReadModel\Doctrine\PlayerUserMappingRepository;
use App\UI\Api\Resource\DiplomacyResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DiplomacyStateProcessor implements ProcessorInterface
{
    public function __construct(
        private DiplomacyApplicationService $diplomacyApplicationService,
        private Security                    $security,
        private PlayerUserMappingRepository $playerUserMappingRepository,
        private DiplomacyViewRepository     $diplomacyViewRepository,
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        try {
            match ($operation->getUriTemplate()) {
                '/games/{gameId}/diplomacy/propose' => $this->proposeDiplomacy($uriVariables['gameId'], $data),
                '/diplomacy/{diplomacyId}/accept' => $this->acceptDiplomacy($uriVariables['diplomacyId']),
                '/diplomacy/{diplomacyId}/decline' => $this->declineDiplomacy($uriVariables['diplomacyId']),
                '/diplomacy/{diplomacyId}/end' => $this->endDiplomacy($uriVariables['diplomacyId']),
                default => null,
            };
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    private function getCurrentPlayerId(string $gameId): PlayerId
    {
        $user = $this->security->getUser();
        if (!$user) {
            throw new BadRequestHttpException('User not authenticated');
        }
        $mapping = $this->playerUserMappingRepository->findOneBy([
            'userId' => $user->getId(),
            'gameId' => $gameId
        ]);
        if (!$mapping) {
            throw new BadRequestHttpException('Player not found for this user in this game');
        }
        return new PlayerId($mapping->playerId);
    }

    private function getGameIdFromDiplomacy(string $diplomacyId): string
    {
        $diplomacy = $this->diplomacyViewRepository->find($diplomacyId);
        if (!$diplomacy) {
            throw new NotFoundHttpException('Diplomacy agreement not found');
        }
        return $diplomacy->gameId;
    }

    private function proposeDiplomacy(string $gameId, DiplomacyResource $data): void
    {
        $currentPlayerId = $this->getCurrentPlayerId($gameId);
        $this->diplomacyApplicationService->proposeDiplomacy(
            $currentPlayerId,
            new PlayerId($data->targetId),
            new GameId($gameId),
            AgreementType::from($data->agreementType)
        );
    }

    private function acceptDiplomacy(string $diplomacyId): void
    {
        $gameId = $this->getGameIdFromDiplomacy($diplomacyId);
        $currentPlayerId = $this->getCurrentPlayerId($gameId);
        $this->diplomacyApplicationService->acceptDiplomacy(
            new DiplomacyId($diplomacyId),
            $currentPlayerId
        );
    }

    private function declineDiplomacy(string $diplomacyId): void
    {
        $gameId = $this->getGameIdFromDiplomacy($diplomacyId);
        $currentPlayerId = $this->getCurrentPlayerId($gameId);
        $this->diplomacyApplicationService->declineDiplomacy(
            new DiplomacyId($diplomacyId),
            $currentPlayerId
        );
    }

    private function endDiplomacy(string $diplomacyId): void
    {
        $gameId = $this->getGameIdFromDiplomacy($diplomacyId);
        $currentPlayerId = $this->getCurrentPlayerId($gameId);
        $this->diplomacyApplicationService->endDiplomacy(
            new DiplomacyId($diplomacyId),
            $currentPlayerId
        );
    }
}
