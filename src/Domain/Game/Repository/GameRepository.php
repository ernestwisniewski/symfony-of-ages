<?php

namespace App\Domain\Game\Repository;

use App\Domain\Game\Game;
use Ecotone\Modelling\Attribute\Repository;

interface GameRepository
{
    #[Repository]
    public function findBy(string $gameId): ?Game;
}
