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
     * Create a new ContextGenerator instance.
     *
     * @param  bool  $force
     */
    public function __construct(bool $force = false)
    {
        $this->force = $force;
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
            throw FileAlreadyExistsException::make('Context file', $this->filename, 'project root');
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
        return $this->findLaravelRoot() . DIRECTORY_SEPARATOR . $this->filename;
    }
}
