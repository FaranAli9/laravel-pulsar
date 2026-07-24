<?php

namespace Faran\Pulsar\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to access a service that doesn't exist.
 *
 * @phpstan-consistent-constructor
 */
class ServiceDoesNotExistException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public static function make(string $serviceName): static
    {
        return new static("Service [{$serviceName}] does not exist!");
    }
}
