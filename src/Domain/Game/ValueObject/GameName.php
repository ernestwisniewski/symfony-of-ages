<?php

namespace App\Domain\Game\ValueObject;

use App\Domain\Shared\ValueObject\ValidationConstants;
use App\Domain\Shared\Exception\DomainException;

final readonly class GameName
{
    public function __construct(
        private string $value
    )
    {
        $this->validate();
    }

    private function validate(): void
    {
        if (strlen($this->value) < ValidationConstants::MIN_GAME_NAME_LENGTH) {
            throw new DomainException('Game name must be at least ' . ValidationConstants::MIN_GAME_NAME_LENGTH . ' characters long');
        }

        if (strlen($this->value) > ValidationConstants::MAX_GAME_NAME_LENGTH_DOMAIN) {
            throw new DomainException('Game name cannot exceed ' . ValidationConstants::MAX_GAME_NAME_LENGTH_DOMAIN . ' characters');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
