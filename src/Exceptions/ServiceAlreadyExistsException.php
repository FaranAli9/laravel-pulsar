<?php

namespace Faran\Pulse\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to create a service that already exists.
 */
class ServiceAlreadyExistsException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $serviceName
     * @return static
     */
    public static function make(string $serviceName): static
    {
        return new static("Service [{$serviceName}] already exists!");
    }
}
