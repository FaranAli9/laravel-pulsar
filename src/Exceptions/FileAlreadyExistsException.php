<?php

namespace Faran\Pulsar\Exceptions;

use Exception;

/**
 * Exception thrown when trying to create a file that already exists.
 */
class FileAlreadyExistsException extends Exception
{
    /**
     * Create a new FileAlreadyExistsException instance.
     *
     * @param  string  $fileType
     * @param  string  $fileName
     * @param  string  $location
     * @return static
     */
    public static function make(string $fileType, string $fileName, string $location): static
    {
        return new static("{$fileType} [{$fileName}] already exists in {$location}!");
    }
}