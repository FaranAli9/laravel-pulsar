<?php

namespace Faran\Pulse\Commands;

use Exception;
use Faran\Pulse\Generators\ServiceGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:service',
    description: 'Create a new Service'
)]
class MakeServiceCommand extends PulseCommand
{
    /**
     * Handle the command execution.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        try {
            $generator = new ServiceGenerator($name);
            $generator->generate();

            $this->line();
            $this->success("{$name} Service created successfully!");
            $this->info("Location: app/Services/{$name}");
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
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the service');
    }
}
