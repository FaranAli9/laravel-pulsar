<?php

namespace Faran\Pulsar\Exceptions;

use Exception;

/**
 * Exception thrown when a Laravel bootstrap file cannot be patched safely.
 *
 * @phpstan-consistent-constructor
 */
class UnexpectedBootstrapFileException extends Exception
{
    /**
     * Create a new exception with recovery instructions.
     */
    public static function make(string $path, string $reason): static
    {
        return new static(
            "Cannot safely patch [{$path}]: {$reason}\n\n"
            ."No files were changed. Add these calls to the Application::configure() chain manually:\n\n"
            ."    ->withEvents(discover: [ app_path('Pulsar/Domain/*/Listeners') ])\n"
            ."    ->withCommands([ ...(glob(app_path('Pulsar/Services/*/Modules/*/Commands'), GLOB_ONLYDIR) ?: []) ])\n\n"
            .'Then ensure App\\Providers\\PulsarServiceProvider::class is listed in bootstrap/providers.php.'
        );
    }
}
