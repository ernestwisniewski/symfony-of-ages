<?php

namespace App\Application\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ResourceNotFoundException extends NotFoundHttpException
{
    public static function gameNotFound(string $gameId): self
    {
        return new self("Game with ID $gameId not found");
    }

    public static function unitNotFound(string $unitId): self
    {
        return new self("Unit with ID $unitId not found");
    }

    public static function cityNotFound(string $cityId): self
    {
        return new self("City with ID $cityId not found");
    }

    public static function technologyNotFound(string $technologyId): self
    {
        return new self("Technology with ID $technologyId not found");
    }

    public static function mapNotFound(string $gameId): self
    {
        return new self("Map for game $gameId not found");
    }

    public static function diplomacyNotFound(string $diplomacyId): self
    {
        return new self("Diplomacy agreement with ID $diplomacyId not found");
    }

    public static function playerNotFound(string $playerId): self
    {
        return new self("Player with ID $playerId not found");
    }

    public static function technologyTreeNotFound(string $playerId): self
    {
        return new self("Technology tree for player $playerId not found");
    }
}
