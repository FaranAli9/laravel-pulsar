<?php

namespace Faran\Pulsar\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to access a domain that doesn't exist.
 *
 * @phpstan-consistent-constructor
 */
class DomainDoesNotExistException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public static function make(string $domainName): static
    {
        return new static("Domain [{$domainName}] does not exist!");
    }
}
