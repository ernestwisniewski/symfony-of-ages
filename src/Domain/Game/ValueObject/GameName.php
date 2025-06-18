<?php

namespace App\Domain\Game\ValueObject;

use App\Domain\Game\Exception\GameException;
use App\Domain\Shared\ValueObject\ValidationConstants;

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
            throw InvalidGameNameException::tooShort($this->value, ValidationConstants::MIN_GAME_NAME_LENGTH);
        }
        if (strlen($this->value) > ValidationConstants::MAX_GAME_NAME_LENGTH_DOMAIN) {
            throw InvalidGameNameException::tooLong($this->value, ValidationConstants::MAX_GAME_NAME_LENGTH_DOMAIN);
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

class InvalidGameNameException extends GameException
{
    public static function tooShort(string $name, int $min): self
    {
        return new self('Game name must be at least ' . $min . ' characters long. Given: ' . $name);
    }

    public static function tooLong(string $name, int $max): self
    {
        return new self('Game name cannot exceed ' . $max . ' characters. Given: ' . $name);
    }
}
