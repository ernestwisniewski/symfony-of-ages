<?php

namespace App\Domain\City;

use App\Application\City\Command\FoundCityCommand;
use App\Domain\City\Event\CityWasFounded;
use App\Domain\City\ValueObject\CityId;
use App\Domain\City\ValueObject\CityName;
use App\Domain\Map\ValueObject\Position;
use App\Domain\Player\ValueObject\PlayerId;
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
    public CityId $cityId;
    public PlayerId $ownerId;
    public CityName $name;
    public Position $position;

    #[CommandHandler]
    public static function found(FoundCityCommand $command): array
    {
        return [
            new CityWasFounded(
                (string) $command->cityId,
                (string) $command->ownerId,
                (string)$command->name,
                $command->position->toArray()
            )
        ];
    }

    #[EventSourcingHandler]
    public function whenCityIsFounded(CityWasFounded $event): void
    {
        $this->cityId = new CityId($event->cityId);
        $this->ownerId = new PlayerId($event->ownerId);
        $this->name = new CityName($event->name);
        $this->position = Position::fromArray($event->position);
    }
}
