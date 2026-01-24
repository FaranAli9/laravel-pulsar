<?php

namespace Faran\Pulse\Traits;

use Exception;

trait Finder
{
    /**
     * Get the root of the source directory.
     */
    public function findSourceRoot(): string
    {
        // If running from a Laravel project, find the app directory
        $laravelRoot = $this->findLaravelRoot();
        
        return $laravelRoot . DIRECTORY_SEPARATOR . $this->getSourceDirectoryName();
    }

    /**
     * Find the Laravel project root directory.
     */
    protected function findLaravelRoot(): string
    {
        // Start from current working directory
        $dir = getcwd();
        
        // Look for composer.json to identify Laravel root
        while ($dir !== dirname($dir)) {
            if (file_exists($dir . DIRECTORY_SEPARATOR . 'composer.json') && 
                file_exists($dir . DIRECTORY_SEPARATOR . 'artisan')) {
                return $dir;
            }
            $dir = dirname($dir);
        }
        
        throw new Exception('Could not find Laravel project root. Make sure you are running this command from within a Laravel project.');
    }

    /**
     * Check if a service exists.
     */
    public function serviceExists(string $name): bool
    {
        return file_exists($this->findServicesRootPath() . DIRECTORY_SEPARATOR . $name);
    }

    /**
     * Find the root path of all the services.
     */
    public function findServicesRootPath(): string
    {
        return $this->findSourceRoot() . DIRECTORY_SEPARATOR . 'Services';
    }

    /**
     * Find the root path of all the domains.
     */
    public function findDomainRootPath(): string
    {
        return $this->findSourceRoot() . DIRECTORY_SEPARATOR . 'Domain';
    }

    /**
     * Find the namespace from composer.json.
     *
     * @throws Exception
     */
    public function findNamespace(?string $dir = null): string
    {
        $dir = $dir ?? $this->getSourceDirectoryName();

        // Read composer.json file contents to determine the namespace
        $laravelRoot = $this->findLaravelRoot();
        $composerPath = $laravelRoot . DIRECTORY_SEPARATOR . 'composer.json';
        
        if (!file_exists($composerPath)) {
            throw new Exception('composer.json not found in Laravel project root');
        }
        
        $composer = json_decode(file_get_contents($composerPath), true);

        // See which one refers to the directory
        foreach ($composer['autoload']['psr-4'] as $namespace => $directory) {
            $directory = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $directory);
            if ($directory === $dir . DIRECTORY_SEPARATOR) {
                return trim($namespace, '\\');
            }
        }

        throw new Exception('App namespace not set in composer.json');
    }

    /**
     * Find the service namespace.
     *
     * @throws Exception
     */
    public function findServiceNamespace(string $service): string
    {
        $root = $this->findRootNamespace();

        return "$root\\Services\\$service";
    }

    /**
     * Find the domain namespace.
     *
     * @throws Exception
     */
    public function findDomainNamespace(string $domain): string
    {
        $root = $this->findRootNamespace();

        return "$root\\Domain\\$domain";
    }

    /**
     * Find the root namespace.
     *
     * @throws Exception
     */
    public function findRootNamespace(): string
    {
        return $this->findNamespace($this->getSourceDirectoryName());
    }

    /**
     * Get the source directory name.
     */
    public function getSourceDirectoryName(): string
    {
        return 'app';
    }

    /**
     * Get the relative version of the given real path.
     */
    protected function relativeFromReal(string $path, string $needle = ''): string
    {
        if (!$needle) {
            $needle = $this->getSourceDirectoryName() . DIRECTORY_SEPARATOR;
        }

        return strstr($path, $needle);
    }
}
