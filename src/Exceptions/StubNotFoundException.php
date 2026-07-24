<?php

namespace Faran\Pulsar\Exceptions;

use Exception;

/**
 * Exception thrown when a stub file cannot be found.
 *
 * @phpstan-consistent-constructor
 */
class StubNotFoundException extends Exception
{
    /**
     * Create a new StubNotFoundException instance.
     */
    public static function make(string $stubPath): static
    {
        return new static("Stub file not found: {$stubPath}");
    }
}
