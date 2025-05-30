<?php

namespace App\Application\Map\Exception;

use RuntimeException;
use Throwable;

/**
 * Base class for all map application exceptions
 *
 * Represents application-level failures such as map generation errors,
 * validation failures, or service coordination problems.
 */
abstract class MapApplicationException extends RuntimeException
{
    public function __construct(
        string     $message = '',
        int         $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
