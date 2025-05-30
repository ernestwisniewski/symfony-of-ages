<?php

namespace App\Application\Player\Exception;

/**
 * Exception thrown when player service operations fail
 *
 * Used for application-level failures in player services such as
 * coordination failures, external service errors, or orchestration problems.
 */
class PlayerServiceException extends PlayerApplicationException
{
    public static function creationFailed(string $reason, ?\Throwable $previous = null): self
    {
        return new self("Player creation failed: {$reason}", 0, $previous);
    }

    public static function movementCalculationFailed(string $reason, ?\Throwable $previous = null): self
    {
        return new self("Movement calculation failed: {$reason}", 0, $previous);
    }

    public static function statusRetrievalFailed(string $reason, ?\Throwable $previous = null): self
    {
        return new self("Player status retrieval failed: {$reason}", 0, $previous);
    }

    public static function tacticalAnalysisFailed(string $reason, ?\Throwable $previous = null): self
    {
        return new self("Tactical analysis failed: {$reason}", 0, $previous);
    }

    public static function sessionDataCorrupted(?\Throwable $previous = null): self
    {
        return new self("Player session data is corrupted", 0, $previous);
    }
} 