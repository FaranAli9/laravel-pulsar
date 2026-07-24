<?php

namespace Faran\Pulsar\Exceptions;

use Exception;

/**
 * Exception thrown when trying to create a file that already exists.
 *
 * @phpstan-consistent-constructor
 */
class FileAlreadyExistsException extends Exception
{
    /**
     * Create a new FileAlreadyExistsException instance.
     */
    public static function make(string $fileType, string $fileName, string $location): static
    {
        return new static("{$fileType} [{$fileName}] already exists in {$location}!");
    }
}
