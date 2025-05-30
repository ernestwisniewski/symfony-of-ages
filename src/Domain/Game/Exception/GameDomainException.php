<?php

namespace App\Domain\Game\Exception;

use DomainException;
use Throwable;

/**
 * Base class for all game domain exceptions
 *
 * Represents domain-level violations and business rule failures
 * within the game domain context.
 */
abstract class GameDomainException extends DomainException
{
    public function __construct(
        string     $message = '',
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
