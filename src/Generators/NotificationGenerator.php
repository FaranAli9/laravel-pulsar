<?php

namespace Faran\Pulsar\Generators;

use Faran\Pulsar\Exceptions\FileAlreadyExistsException;

class NotificationGenerator extends Generator
{
    /**
     * Create a new NotificationGenerator instance.
     */
    public function __construct(
        protected string $name,
        protected string $domain,
    ) {}

    /**
     * Generate the domain notification.
     *
     * @throws FileAlreadyExistsException
     */
    public function generate(): string
    {
        $this->validateInputs([$this->name], [$this->domain]);
        $this->validateDomainExists($this->domain);

        $notificationsPath = $this->findDomainRootPath().DIRECTORY_SEPARATOR.$this->domain
            .DIRECTORY_SEPARATOR.'Notifications';
        $filePath = $notificationsPath.DIRECTORY_SEPARATOR.$this->name.'.php';

        if ($this->fileExists($filePath)) {
            throw FileAlreadyExistsException::make('Notification', $this->name, $this->domain);
        }

        $this->createDirectory($notificationsPath);
        $stub = $this->loadStub($this->getStubPath('notification'));
        $content = $this->replaceStubPlaceholders($stub, [
            'namespace' => $this->findDomainNamespace($this->domain).'\\Notifications',
            'name' => $this->name,
        ]);
        $this->createFile($filePath, $content);

        return $this->getRelativePath($filePath);
    }
}
