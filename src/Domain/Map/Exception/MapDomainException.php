<?php

namespace App\Domain\Map\Exception;

use DomainException;
use Throwable;

/**
 * Base class for all map domain exceptions
 *
 * Represents domain-level violations and business rule failures
 * within the map domain context.
 */
abstract class MapDomainException extends DomainException
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
