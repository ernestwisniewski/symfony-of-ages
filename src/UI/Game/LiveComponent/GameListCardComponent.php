<?php
declare(strict_types=1);

namespace App\UI\Game\LiveComponent;

use App\Application\Game\Command\JoinGameCommand;
use App\Application\Game\Command\StartGameCommand;
use App\Application\Player\Query\GetUserIdsByGameQuery;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Timestamp;
use App\Domain\Shared\ValueObject\UserId;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class GameListCardComponent
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp]
    public array $game = [];

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus   $queryBus,
        private readonly Security   $security
    )
    {
    }

    public function mount(array $game): void
    {
        $this->game = $game;
    }

    #[LiveAction('startGame')]
    public function startGame(): void
    {
        $this->commandBus->send(new StartGameCommand(
            gameId: new GameId($this->game['id']),
            startedAt: Timestamp::now()
        ));
        $this->dispatchBrowserEvent('game:started', [
            'gameId' => $this->game['id']
        ]);
    }

    #[LiveAction('joinGame')]
    public function joinGame(): void
    {
        $this->commandBus->send(new JoinGameCommand(
            gameId: new GameId($this->game['id']),
            playerId: new PlayerId(Uuid::v4()->toRfc4122()),
            userId: new UserId($this->security->getUser()->getId()),
            joinedAt: Timestamp::now()
        ));
        $this->dispatchBrowserEvent('game:joined', [
            'gameId' => $this->game['id'],
        ]);
    }

    public function getIsMyGame(): bool
    {
        return $this->game['userId'] === $this->security->getUser()?->getId();
    }

    public function getUserIsParticipant(): bool
    {
        $currentUserId = $this->security->getUser()?->getId();
        if (!$currentUserId) {
            return false;
        }
        $participantUserIds = $this->queryBus->send(new GetUserIdsByGameQuery(
            new GameId($this->game['id'])
        ));
        return in_array($currentUserId, $participantUserIds);
    }

    public function getCanStart(): bool
    {
        return $this->getIsMyGame() &&
            !$this->game['isStarted'] &&
            count($this->game['players']) >= 2;
    }

    public function getCanJoin(): bool
    {
        return !$this->getIsMyGame() &&
            !$this->getUserIsParticipant() &&
            !$this->game['isStarted'] &&
            count($this->game['players']) < 4;
    }

    public function getCanPlay(): bool
    {
        return ($this->getIsMyGame() || $this->getUserIsParticipant()) &&
            $this->game['isStarted'];
    }
}
