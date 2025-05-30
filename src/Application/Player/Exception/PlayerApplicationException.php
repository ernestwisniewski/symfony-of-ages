<?php

namespace App\Application\Player\Exception;

use RuntimeException;
use Throwable;

/**
 * Base class for all player application exceptions
 *
 * Represents application-level failures such as service errors,
 * external system failures, or orchestration problems.
 */
abstract class PlayerApplicationException extends RuntimeException
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
