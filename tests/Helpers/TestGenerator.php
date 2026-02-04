<?php

namespace Tests\Helpers;

use Faran\Pulsar\Generators\Generator;

/**
 * Test implementation of abstract Generator class.
 * Exposes protected methods for unit testing.
 */
class TestGenerator extends Generator
{
    /**
     * Dummy implementation of abstract generate method.
     */
    public function generate(): string
    {
        return 'test';
    }
    
    /**
     * Expose validateName for testing.
     */
    public function testValidateName(string $name, string $type = 'class'): void
    {
        $this->validateName($name, $type);
    }
    
    /**
     * Expose sanitizeDirectoryName for testing.
     */
    public function testSanitizeDirectoryName(string $name, string $type = 'directory'): string
    {
        return $this->sanitizeDirectoryName($name, $type);
    }
    
    /**
     * Expose replaceStubPlaceholders for testing.
     */
    public function testReplaceStubPlaceholders(string $stub, array $replacements): string
    {
        return $this->replaceStubPlaceholders($stub, $replacements);
    }
    
    /**
     * Expose generateSlug for testing.
     */
    public function testGenerateSlug(string $name): string
    {
        return $this->generateSlug($name);
    }
    
    /**
     * Expose getStubPath for testing.
     */
    public function testGetStubPath(string $stubName): string
    {
        return $this->getStubPath($stubName);
    }
    
    /**
     * Expose getRelativePath for testing.
     */
    public function testGetRelativePath(string $filePath): string
    {
        return $this->getRelativePath($filePath);
    }
    
    /**
     * Expose loadStub for testing.
     */
    public function testLoadStub(string $stubPath): string
    {
        return $this->loadStub($stubPath);
    }
    
    /**
     * Expose createDirectory for testing.
     */
    public function testCreateDirectory(string $path, int $mode = 0755, bool $recursive = true): void
    {
        $this->createDirectory($path, $mode, $recursive);
    }
    
    /**
     * Expose createFile for testing.
     */
    public function testCreateFile(string $path, string $contents): void
    {
        $this->createFile($path, $contents);
    }
    
    /**
     * Expose createRecursiveDirectories for testing.
     */
    public function testCreateRecursiveDirectories(string $root, array $elements): string
    {
        return $this->createRecursiveDirectories($root, $elements);
    }
    
    /**
     * Expose createGitkeep for testing.
     */
    public function testCreateGitkeep(string $directory): void
    {
        $this->createGitkeep($directory);
    }
    
    /**
     * Expose fileExists for testing.
     */
    public function testFileExists(string $path): bool
    {
        return $this->fileExists($path);
    }
}
