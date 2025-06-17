<?php
declare(strict_types=1);

namespace App\Application\Unit\Query;

use App\Domain\Game\ValueObject\GameId;

final readonly class GetUnitsByGameQuery
{
    public function __construct(
        public GameId $gameId,
    )
    {
    }
}
