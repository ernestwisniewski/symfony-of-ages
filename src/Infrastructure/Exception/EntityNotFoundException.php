<?php

namespace App\Infrastructure\Exception;

final class EntityNotFoundException extends InfrastructureException
{
    public static function gameViewNotFound(string $gameId): self
    {
        return new self("GameViewEntity for ID $gameId not found");
    }

    public static function unitViewNotFound(string $unitId): self
    {
        return new self("UnitViewEntity for ID $unitId not found");
    }

    public static function cityViewNotFound(string $cityId): self
    {
        return new self("CityView for ID $cityId not found");
    }

    public static function mapViewNotFound(string $gameId): self
    {
        return new self("MapViewEntity for game ID $gameId not found");
    }

    public static function activePlayerNotFound(string $playerId): self
    {
        return new self("Active player $playerId not found in players list");
    }
}
