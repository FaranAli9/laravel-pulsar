<?php

namespace Faran\Pulse\Generators;

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
     */
    protected function loadStub(string $stubPath): string
    {
        if (!file_exists($stubPath)) {
            throw new \Exception("Stub file not found: {$stubPath}");
        }

        return file_get_contents($stubPath);
    }
}
