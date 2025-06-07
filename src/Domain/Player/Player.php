<?php

namespace App\Domain\Player;

use App\Application\Player\Command\CreatePlayerCommand;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Player\Event\PlayerWasCreated;
use App\Domain\Player\ValueObject\PlayerId;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
class Player
{
    use WithAggregateVersioning;

    #[Identifier]
    private PlayerId $playerId;
    private GameId $gameId;

    #[CommandHandler]
    public static function create(CreatePlayerCommand $command): array
    {
        return [
            new PlayerWasCreated(
                (string)$command->playerId,
                (string)$command->gameId
            )
        ];
    }

    #[EventSourcingHandler]
    public function whenPlayerWasCreated(PlayerWasCreated $event): void
    {
        $this->playerId = new PlayerId($event->playerId);
        $this->gameId = new GameId($event->gameId);
    }
}
