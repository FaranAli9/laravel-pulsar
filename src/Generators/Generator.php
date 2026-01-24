<?php

namespace Faran\Pulse\Generators;

use Faran\Pulse\Exceptions\InvalidNameException;
use Faran\Pulse\Exceptions\StubNotFoundException;
use Faran\Pulse\Traits\Finder;

/**
 * Base generator class for Pulse code generation.
 * 
 * Provides common functionality for creating directories and files.
 */
abstract class Generator
{
    use Finder;

    /**
     * Create a directory if it doesn't exist.
     *
     * @param  string  $path
     * @param  int  $mode
     * @param  bool  $recursive
     * @return void
     */
    protected function createDirectory(string $path, int $mode = 0755, bool $recursive = true): void
    {
        if (!is_dir($path)) {
            mkdir($path, $mode, $recursive);
        }
    }

    /**
     * Create a file with the given contents.
     *
     * @param  string  $path
     * @param  string  $contents
     * @return void
     */
    protected function createFile(string $path, string $contents): void
    {
        file_put_contents($path, $contents);
    }

    /**
     * Create nested directories from an array of path elements.
     *
     * @param  string  $root
     * @param  array  $elements
     * @return string The full path created
     */
    protected function createRecursiveDirectories(string $root, array $elements): string
    {
        $path = $root;

        foreach ($elements as $element) {
            $path .= DIRECTORY_SEPARATOR . $element;
            $this->createDirectory($path);
        }

        return $path;
    }

    /**
     * Create a .gitkeep file in the given directory.
     *
     * @param  string  $directory
     * @return void
     */
    protected function createGitkeep(string $directory): void
    {
        $gitkeepPath = $directory . DIRECTORY_SEPARATOR . '.gitkeep';
        
        if (!file_exists($gitkeepPath)) {
            $this->createFile($gitkeepPath, '');
        }
    }

    /**
     * Check if a file exists.
     *
     * @param  string  $path
     * @return bool
     */
    protected function fileExists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Generate a URL-friendly slug from a string.
     *
     * @param  string  $name
     * @return string
     */
    protected function generateSlug(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
    }

    /**
     * Replace placeholders in stub content.
     *
     * @param  string  $stub
     * @param  array  $replacements
     * @return string
     */
    protected function replaceStubPlaceholders(string $stub, array $replacements): string
    {
        foreach ($replacements as $search => $replace) {
            $stub = str_replace("{{" . $search . "}}", $replace, $stub);
        }

        return $stub;
    }

    /**
     * Load stub file contents.
     *
     * @param  string  $stubPath
     * @return string
     * @throws StubNotFoundException
     */
    protected function loadStub(string $stubPath): string
    {
        if (!file_exists($stubPath)) {
            throw StubNotFoundException::make($stubPath);
        }

        return file_get_contents($stubPath);
    }

    /**
     * Get the path to a stub file.
     *
     * @param  string  $stubName
     * @return string
     */
    protected function getStubPath(string $stubName): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . $stubName . '.stub';
    }

    /**
     * Validate and sanitize input name.
     *
     * @param  string  $name
     * @param  string  $type
     * @return void
     * @throws InvalidNameException
     */
    protected function validateName(string $name, string $type = 'class'): void
    {
        // Check for empty name
        if (empty(trim($name))) {
            throw InvalidNameException::make($name, 'Name cannot be empty');
        }

        // Check length (reasonable limit)
        if (strlen($name) > 100) {
            throw InvalidNameException::make($name, 'Name is too long (max 100 characters)');
        }

        // Check for valid PHP class name characters (letters, numbers, underscores, backslashes for namespaces)
        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*$/', $name)) {
            throw InvalidNameException::invalidCharacters($name, $type);
        }

        // Check for reserved PHP keywords
        $reservedWords = [
            'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 
            'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty',
            'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit',
            'extends', 'final', 'finally', 'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if',
            'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset',
            'list', 'match', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public',
            'readonly', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait',
            'try', 'unset', 'use', 'var', 'while', 'xor', 'yield'
        ];

        $nameParts = explode('\\', $name);
        foreach ($nameParts as $part) {
            $lowerPart = strtolower($part);
            if (in_array($lowerPart, $reservedWords)) {
                throw InvalidNameException::reservedKeyword($part, $type);
            }
        }
    }

    /**
     * Sanitize service/module names to prevent path traversal.
     *
     * @param  string  $name
     * @param  string  $type
     * @return string
     * @throws InvalidNameException
     */
    protected function sanitizeDirectoryName(string $name, string $type = 'directory'): string
    {
        // Remove any path traversal attempts
        $sanitized = str_replace(['..', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $name);
        
        // Remove leading/trailing whitespace and dots
        $sanitized = trim($sanitized, " \t\n\r\0\x0B.");
        
        if ($sanitized !== $name) {
            throw InvalidNameException::make($name, 'Contains forbidden characters or path traversal attempts');
        }

        if (empty($sanitized)) {
            throw InvalidNameException::make($name, 'Name cannot be empty after sanitization');
        }

        return $sanitized;
    }

    /**
     * Get the relative path from the Laravel root.
     *
     * @param  string  $filePath
     * @return string
     */
    protected function getRelativePath(string $filePath): string
    {
        return str_replace($this->findLaravelRoot() . DIRECTORY_SEPARATOR, '', $filePath);
    }
}
