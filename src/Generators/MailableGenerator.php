<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class MailableGenerator extends Generator
{
    /**
     * Create a new MailableGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
    ) {}

    /**
     * Generate the domain mailable.
     *
     * @throws FileAlreadyExistsException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->validateDomainExists($this->domain);

        $mailPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain
            .DIRECTORY_SEPARATOR.'Mail';
        $filePath = $mailPath.DIRECTORY_SEPARATOR.$this->name.'.php';

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Mailable', $this->name, $this->domain);
        }

        $this->createDirectory($mailPath);
        $stub = $this->loadStub($this->getStubPath('mailable'));
        $content = $this->replaceStubPlaceholders($stub, [
            'namespace' => $this->findDomainNamespace($this->domain).'\\Mail',
            'view' => 'mail.'.$this->generateSlug($this->name),
            'name' => $this->name,
        ]);
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }
}
