<?php

namespace Faran\Pulsar\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid name is provided for class, service, module, etc.
 *
 * @phpstan-consistent-constructor
 */
class InvalidNameException extends Exception
{
    /**
     * Create a new InvalidNameException instance.
     */
    public static function make(string $name, string $reason): static
    {
        return new static("Invalid name '{$name}': {$reason}");
    }

    /**
     * Create exception for reserved PHP keyword.
     */
    public static function reservedKeyword(string $name, string $type): static
    {
        return new static("'{$name}' is a reserved PHP keyword and cannot be used as {$type} name");
    }

    /**
     * Create exception for invalid characters.
     */
    public static function invalidCharacters(string $name, string $type): static
    {
        return new static("Invalid {$type} name '{$name}'. Use only letters, numbers, underscores, and backslashes");
    }
}
