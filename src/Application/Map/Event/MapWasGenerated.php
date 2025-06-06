<?php

namespace App\Application\Map\Event;

use App\Domain\Game\ValueObject\GameId;

final readonly class MapWasGenerated
{
public function __construct(public string $gameId, public array $mapTiles)
{

}
}
