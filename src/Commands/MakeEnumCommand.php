<?php

namespace Faran\Pulsar\Commands;

use Exception;
use Faran\Pulsar\Generators\EnumGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:enum',
    description: 'Create a new domain enum',
)]
class MakeEnumCommand extends PulsarCommand
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
            $generator = new EnumGenerator($name, $domain);
            $filePath = $generator->generate();

            $this->line();
            $this->success("Enum created successfully");
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
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the enum');
        $this->addArgument('domain', InputArgument::REQUIRED, 'The name of the domain');
    }
}
