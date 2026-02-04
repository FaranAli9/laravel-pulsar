<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class SkillGenerator extends Generator
{
    /**
     * The default output path relative to Laravel root.
     */
    protected string $defaultPath = '.claude' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . 'pulsar' . DIRECTORY_SEPARATOR . 'SKILL.md';

    /**
     * Whether to overwrite an existing file.
     */
    protected bool $force;

    /**
     * Custom output path.
     */
    protected ?string $customPath = null;

    /**
     * Create a new SkillGenerator instance.
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
     * Generate the skill file.
     *
     * @throws FileAlreadyExistsException
     */
    public function generate(): string
    {
        $filePath = $this->getSkillPath();

        if (!$this->force && $this->fileExists($filePath)) {
            $displayPath = $this->customPath ?? $this->defaultPath;
            throw FileAlreadyExistsException::make('Skill file', basename($displayPath), dirname($displayPath));
        }

        // Create parent directories
        $parentDir = dirname($filePath);
        if (!is_dir($parentDir)) {
            $this->createDirectory($parentDir);
        }

        $content = $this->loadStub($this->getStubPath('skill'));
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }

    /**
     * Get the skill file path.
     */
    protected function getSkillPath(): string
    {
        if ($this->customPath) {
            // Handle relative paths from Laravel root
            if (!str_starts_with($this->customPath, DIRECTORY_SEPARATOR)) {
                return $this->findLaravelRoot() . DIRECTORY_SEPARATOR . $this->customPath;
            }
            return $this->customPath;
        }

        return $this->findLaravelRoot() . DIRECTORY_SEPARATOR . $this->defaultPath;
    }
}
