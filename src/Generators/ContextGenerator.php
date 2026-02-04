<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class ContextGenerator extends Generator
{
    /**
     * The output filename.
     */
    protected string $filename = 'PULSAR.md';

    /**
     * Whether to overwrite an existing file.
     */
    protected bool $force;

    /**
     * Custom output path.
     */
    protected ?string $customPath = null;

    /**
     * Create a new ContextGenerator instance.
     *
     * @param  bool  $force
     * @param  string|null  $path
     */
    public function __construct(bool $force = false, ?string $path = null)
    {
        $this->force = $force;
        $this->customPath = $path;
    }

    /**
     * Generate the context file.
     *
     * @throws FileAlreadyExistsException
     */
    public function generate(): string
    {
        $filePath = $this->getContextPath();

        if (!$this->force && $this->fileExists($filePath)) {
            $displayPath = $this->customPath ?? $this->filename;
            throw FileAlreadyExistsException::make('Context file', basename($displayPath), dirname($displayPath));
        }

        // Create parent directories if custom path
        if ($this->customPath) {
            $parentDir = dirname($filePath);
            if (!is_dir($parentDir)) {
                $this->createDirectory($parentDir);
            }
        }

        $content = $this->loadStub($this->getStubPath('context'));
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Get the context file path.
     */
    protected function getContextPath(): string
    {
        if ($this->customPath) {
            // Handle relative paths from Laravel root
            if (!str_starts_with($this->customPath, DIRECTORY_SEPARATOR)) {
                return $this->findLaravelRoot() . DIRECTORY_SEPARATOR . $this->customPath;
            }
            return $this->customPath;
        }

        return $this->findLaravelRoot() . DIRECTORY_SEPARATOR . $this->filename;
    }
}
