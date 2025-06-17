<?php

namespace App\Domain\Technology;

use App\Domain\Shared\ValueObject\ValidationConstants;
use App\Domain\Technology\ValueObject\TechnologyId;
use DomainException;

class Technology
{
    public TechnologyId $id;
    public string $name;
    public string $description;
    public int $cost;
    public array $prerequisites = [];
    public array $effects = [];

    public static function create(
        TechnologyId $id,
        string       $name,
        string       $description,
        int          $cost,
        array        $prerequisites = [],
        array        $effects = []
    ): self
    {
        $technology = new self();
        $technology->id = $id;
        $technology->name = $name;
        $technology->description = $description;
        $technology->cost = $cost;
        $technology->prerequisites = $prerequisites;
        $technology->effects = $effects;
        $technology->validate();
        return $technology;
    }

    private function validate(): void
    {
        if (trim($this->name) === '' || mb_strlen($this->name) > ValidationConstants::MAX_TECHNOLOGY_NAME_LENGTH) {
            throw new DomainException('Invalid technology name.');
        }
        if (trim($this->description) === '' || mb_strlen($this->description) > ValidationConstants::MAX_TECHNOLOGY_DESCRIPTION_LENGTH) {
            throw new DomainException('Invalid technology description.');
        }
        if ($this->cost < ValidationConstants::MIN_TECHNOLOGY_COST) {
            throw new DomainException('Technology cost cannot be negative.');
        }
        if ($this->cost > ValidationConstants::MAX_TECHNOLOGY_COST) {
            throw new DomainException('Technology cost cannot exceed ' . ValidationConstants::MAX_TECHNOLOGY_COST);
        }
    }

    public function hasPrerequisites(): bool
    {
        return !empty($this->prerequisites);
    }

    public function getPrerequisitesIds(): array
    {
        return array_map(fn(TechnologyId $id) => (string)$id, $this->prerequisites);
    }

    public function hasEffects(): bool
    {
        return !empty($this->effects);
    }

    public function getEffects(): array
    {
        return $this->effects;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
