<?php

namespace App\Domain\Game\Repository;

use App\Domain\Game\Game;
use Ecotone\Modelling\Attribute\Repository;

interface GameRepository
{
    /** Nullable return type. This will return null, when not null */
    #[Repository]
    public function findBy(string $gameId): ?Game;
}
