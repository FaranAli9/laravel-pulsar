<?php

namespace Faran\Pulse\Commands;

use Exception;
use Faran\Pulse\Generators\DtoGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(
    name: 'make:dto',
    description: 'Create a new domain DTO class',
)]
class MakeDtoCommand extends PulseCommand
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
            $generator = new DtoGenerator($name, $domain);
            $filePath = $generator->generate();

            $this->line();
            $this->success("DTO created successfully");
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
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the DTO');
        $this->addArgument('domain', InputArgument::REQUIRED, 'The name of the domain');
    }
}
