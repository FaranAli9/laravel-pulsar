<?php

namespace Faran\Pulsar\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to create a service that already exists.
 *
 * @phpstan-consistent-constructor
 */
class ServiceAlreadyExistsException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public static function make(string $serviceName): static
    {
        return new static("Service [{$serviceName}] already exists!");
    }
}
