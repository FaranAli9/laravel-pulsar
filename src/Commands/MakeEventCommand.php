<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\EventGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:event',
    description: 'Create a new domain event class',
)]
class MakeEventCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');

        try {
            $generator = new EventGenerator($name, $domain);
            $filePath = $generator->generate();

            $this->line();
            $this->success("Event created successfully");
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
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the event');
        $this->addArgument('domain', InputArgument::REQUIRED, 'The name of the domain');
    }
}
