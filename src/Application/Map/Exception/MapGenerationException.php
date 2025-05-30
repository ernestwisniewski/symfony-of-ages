<?php

namespace App\Application\Map\Exception;

use Throwable;

/**
 * Exception thrown when map generation fails
 *
 * Used when map generation processes fail due to invalid parameters,
 * generation algorithm errors, or external service failures.
 */
class MapGenerationException extends MapApplicationException
{
    public static function competitiveMapFailed(int $expectedPlayers, ?Throwable $previous = null): self
    {
        return new self("Failed to generate competitive map for {$expectedPlayers} players", 0, $previous);
    }

    public static function themedMapFailed(array $terrainEmphasis, ?Throwable $previous = null): self
    {
        $emphasis = json_encode($terrainEmphasis);
        return new self("Failed to generate themed map with emphasis: {$emphasis}", 0, $previous);
    }

    public static function standardMapFailed(int $rows, int $cols, ?Throwable $previous = null): self
    {
        return new self("Failed to generate standard map ({$rows}x{$cols})", 0, $previous);
    }

    public static function invalidTerrainEmphasis(string $terrain, int $percentage): self
    {
        return new self("Invalid terrain emphasis: {$terrain} with {$percentage}%");
    }

    public static function invalidPlayerCount(int $players): self
    {
        return new self("Invalid player count: {$players}. Must be between 1 and 8.");
    }
}
