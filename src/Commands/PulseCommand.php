<?php

namespace Faran\Pulse\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base command class for Pulse commands.
 * 
 * Provides template method pattern for consistent command structure
 * and helper methods for styled output.
 */
abstract class PulseCommand extends Command
{
    /**
     * The input interface implementation.
     */
    protected InputInterface $input;

    /**
     * The output interface implementation.
     */
    protected OutputInterface $output;

    /**
     * Execute the console command.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        return $this->handle();
    }

    /**
     * Handle the command execution.
     * 
     * Subclasses must implement this method to provide command logic.
     *
     * @return int Exit code (0 for success, non-zero for failure)
     */
    abstract public function handle(): int;

    /**
     * Get the value of a command argument.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function argument(string $key): mixed
    {
        return $this->input->getArgument($key);
    }

    /**
     * Get the value of a command option.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function option(string $key): mixed
    {
        return $this->input->getOption($key);
    }

    /**
     * Write a string as information output.
     *
     * @param  string  $message
     * @return void
     */
    protected function info(string $message): void
    {
        $this->output->writeln("<info>{$message}</info>");
    }

    /**
     * Write a string as success output.
     *
     * @param  string  $message
     * @return void
     */
    protected function success(string $message): void
    {
        $this->output->writeln("<fg=green>✓</> {$message}");
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $message
     * @return void
     */
    protected function error(string $message): void
    {
        $this->output->writeln("<error>✗ {$message}</error>");
    }

    /**
     * Write a string as warning output.
     *
     * @param  string  $message
     * @return void
     */
    protected function warning(string $message): void
    {
        $this->output->writeln("<comment>⚠ {$message}</comment>");
    }

    /**
     * Write a blank line.
     *
     * @return void
     */
    protected function line(): void
    {
        $this->output->writeln('');
    }
}
