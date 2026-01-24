<?php

namespace Faran\Pulse\Commands;

use Exception;
use Faran\Pulse\Generators\ControllerGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:controller',
    description: 'Create a new Controller in a Service Module'
)]
class MakeControllerCommand extends PulseCommand
{
    /**
     * Handle the command execution.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->argument('module');
        $service = $this->argument('service');
        $resource = $this->option('resource');

        try {
            $generator = new ControllerGenerator($name, $service, $module, $resource);
            $filePath = $generator->generate();

            $this->line();
            $this->success("Controller created successfully ");
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
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the controller');
        $this->addArgument('module', InputArgument::REQUIRED, 'The name of the module');
        $this->addArgument('service', InputArgument::REQUIRED, 'The name of the service');
        $this->addOption('resource', 'r', InputOption::VALUE_NONE, 'Generate a resourceful controller with CRUD methods');
    }
}
