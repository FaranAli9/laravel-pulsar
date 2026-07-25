<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\ResourceGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:resource',
    description: 'Create a new service-layer API resource',
)]
class MakeResourceCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->argument('module');
        $service = $this->argument('service');
        $collection = (bool) $this->option('collection');

        try {
            $filePath = (new ResourceGenerator($name, $module, $service, $collection))->generate();

            $this->line();
            $this->success('Resource created successfully');
            $this->line();
            $this->info("Location: {$filePath}");
            $this->line();

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->line();
            $this->error($e->getMessage());
            $this->line();

            return Command::FAILURE;
        }
    }

    /**
     * Configure the command arguments and options.
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the resource');
        $this->addArgument('module', InputArgument::REQUIRED, 'The name of the module');
        $this->addArgument('service', InputArgument::REQUIRED, 'The name of the service');
        $this->addOption('collection', null, InputOption::VALUE_NONE, 'Generate a resource collection');
    }
}
