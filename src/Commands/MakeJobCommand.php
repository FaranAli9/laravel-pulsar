<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\JobGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:job',
    description: 'Create a new workflow-entrypoint job',
)]
class MakeJobCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->argument('module');
        $service = $this->argument('service');

        try {
            $filePath = (new JobGenerator($name, $module, $service))->generate();

            $this->line();
            $this->success('Job created successfully');
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
     * Configure the command arguments.
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the job');
        $this->addArgument('module', InputArgument::REQUIRED, 'The name of the module');
        $this->addArgument('service', InputArgument::REQUIRED, 'The name of the service');
    }
}
