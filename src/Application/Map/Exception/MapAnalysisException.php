<?php

namespace App\Application\Map\Exception;

/**
 * Exception thrown when map analysis fails
 *
 * Used when map analysis operations fail due to data corruption,
 * calculation errors, or service coordination problems.
 */
class MapAnalysisException extends MapApplicationException
{
    public static function statisticsCalculationFailed(?\Throwable $previous = null): self
    {
        return new self("Failed to calculate terrain statistics", 0, $previous);
    }

    public static function strategicAnalysisFailed(?\Throwable $previous = null): self
    {
        return new self("Failed to perform strategic analysis", 0, $previous);
    }

    public static function balanceValidationFailed(?\Throwable $previous = null): self
    {
        return new self("Failed to validate map balance", 0, $previous);
    }

    public static function mapDataCorrupted(): self
    {
        return new self("Map data is corrupted or invalid");
    }

    public static function insufficientData(string $reason): self
    {
        return new self("Insufficient data for analysis: {$reason}");
    }
} 