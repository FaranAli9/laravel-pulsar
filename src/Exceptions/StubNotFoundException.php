<?php

namespace Faran\Pulsar\Exceptions;

use Exception;

/**
 * Exception thrown when a stub file cannot be found.
 */
class StubNotFoundException extends Exception
{
    /**
     * Create a new StubNotFoundException instance.
     *
     * @param  string  $stubPath
     * @return static
     */
    public static function make(string $stubPath): static
    {
        return new static("Stub file not found: {$stubPath}");
    }
}