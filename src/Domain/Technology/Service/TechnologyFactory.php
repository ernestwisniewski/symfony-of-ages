<?php

namespace App\Domain\Technology\Service;

use App\Domain\Technology\Effect\BonusEffect;
use App\Domain\Technology\Effect\UnlockUnitEffect;
use App\Domain\Technology\Technology;
use App\Domain\Technology\ValueObject\TechnologyId;
use App\Domain\Technology\ValueObject\TechnologyType;
use App\Domain\Unit\ValueObject\UnitType;
use Symfony\Component\Uid\Uuid;

final readonly class TechnologyFactory
{
    public function createFromType(TechnologyType $type): Technology
    {
        $technologyId = new TechnologyId(Uuid::v4()->toRfc4122());
        $prerequisites = $this->createPrerequisites($type);
        $effects = $this->createEffects($type);
        return Technology::create(
            $technologyId,
            $type->getDisplayName(),
            $type->getDescription(),
            $type->getCost(),
            $prerequisites,
            $effects
        );
    }

    public function createAllTechnologies(): array
    {
        $technologies = [];
        foreach (TechnologyType::cases() as $type) {
            $technologies[] = $this->createFromType($type);
        }
        return $technologies;
    }

    private function createPrerequisites(TechnologyType $type): array
    {
        return [];
    }

    private function createEffects(TechnologyType $type): array
    {
        return match ($type) {
            TechnologyType::IRON_WORKING => [
                new UnlockUnitEffect(UnitType::WARRIOR),
                new BonusEffect('production', 10, 'cities')
            ],
            TechnologyType::MATHEMATICS => [
                new BonusEffect('science', 15, 'cities')
            ],
            TechnologyType::ARCHITECTURE => [
                new BonusEffect('production', 20, 'cities')
            ],
            TechnologyType::MILITARY_TACTICS => [
                new UnlockUnitEffect(UnitType::ARCHER),
                new BonusEffect('defense', 10, 'units')
            ],
            TechnologyType::ENGINEERING => [
                new UnlockUnitEffect(UnitType::SIEGE_ENGINE),
                new BonusEffect('production', 25, 'cities')
            ],
            default => []
        };
    }
}
