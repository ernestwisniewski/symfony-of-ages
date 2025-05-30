<?php

namespace App\Domain\Player\Exception;

use DomainException;
use Throwable;

/**
 * Base class for all player domain exceptions
 *
 * Represents domain-level violations and business rule failures
 * within the player domain context.
 */
abstract class PlayerDomainException extends DomainException
{
    public function __construct(
        string     $message = '',
        int         $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
