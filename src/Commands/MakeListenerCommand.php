<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\ListenerGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'make:listener',
    description: 'Create a new domain event listener',
)]
class MakeListenerCommand extends PulsarCommand
{
    /**
     * Handle the command execution.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');
        $eventOption = $this->option('event');
        $event = is_string($eventOption) ? $eventOption : null;
        $queued = (bool) $this->option('queued');

        try {
            $filePath = (new ListenerGenerator($name, $domain, $event, $queued))->generate();

            $this->line();
            $this->success('Listener created successfully');
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
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the listener');
        $this->addArgument('domain', InputArgument::REQUIRED, 'The name of the domain');
        $this->addOption('event', null, InputOption::VALUE_REQUIRED, 'The event class or FQCN to listen for');
        $this->addOption('queued', null, InputOption::VALUE_NONE, 'Generate an after-commit queued listener');
    }
}
