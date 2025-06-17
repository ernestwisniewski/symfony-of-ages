<?php

namespace App\Application\Technology\Query;

use App\Domain\Technology\ValueObject\TechnologyId;

final readonly class GetTechnologyDetailsQuery
{
    public function __construct(
        public TechnologyId $technologyId
    )
    {
    }
}
