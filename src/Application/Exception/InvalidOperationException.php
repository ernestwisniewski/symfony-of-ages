<?php

namespace App\Application\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class InvalidOperationException extends BadRequestHttpException
{
    public static function unsupportedOperation(string $operation): self
    {
        return new self("Unsupported operation: $operation");
    }

    public static function userNotAuthenticated(): self
    {
        return new self("User not authenticated");
    }

    public static function playerNotInGame(string $userId, string $gameId): self
    {
        return new self("Player not found for user $userId in game $gameId");
    }

    public static function invalidRequest(string $message): self
    {
        return new self("Invalid request: $message");
    }
}
