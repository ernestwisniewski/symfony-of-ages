<?php

namespace App\Domain\City;

use App\Application\City\Command\FoundCityCommand;
use App\Domain\City\Event\CityWasFounded;
use App\Domain\City\Policy\CityFoundingPolicy;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\Game\ValueObject\GameId;
use App\Domain\Map\ValueObject\TerrainType;
use App\Domain\Player\ValueObject\PlayerId;
use App\Domain\Shared\ValueObject\Position;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
class City
{
    use WithAggregateVersioning;

    #[Identifier]
    private CityId $cityId;
    private PlayerId $ownerId;
    private GameId $gameId;
    private CityName $name;
    private Position $position;

    #[CommandHandler]
    public static function found(
        FoundCityCommand   $command,
        CityFoundingPolicy $cityFoundingPolicy
    ): array
    {
        $cityFoundingPolicy->validateCityFounding(
            $command->position,
            TerrainType::FOREST, // @todo
            $command->existingCityPositions
        );

        return [
            new CityWasFounded(
                (string)$command->cityId,
                (string)$command->ownerId,
                (string)$command->gameId,
                (string)$command->name,
                $command->position->x,
                $command->position->y,
                $command->foundedAt->format()
            )
        ];
    }

    #[EventSourcingHandler]
    public function whenCityIsFounded(CityWasFounded $event): void
    {
        $this->cityId = new CityId($event->cityId);
        $this->ownerId = new PlayerId($event->ownerId);
        $this->gameId = new GameId($event->gameId);
        $this->name = new CityName($event->name);
        $this->position = new Position($event->x, $event->y);
    }
}
