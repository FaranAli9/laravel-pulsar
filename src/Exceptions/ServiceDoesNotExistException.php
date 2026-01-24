<?php

namespace Faran\Pulse\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to access a service that doesn't exist.
 */
class ServiceDoesNotExistException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $serviceName
     * @return static
     */
    public static function make(string $serviceName): static
    {
        return new static("Service [{$serviceName}] does not exist!");
    }
}
