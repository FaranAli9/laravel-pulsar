<?php

namespace Faran\Pulse\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'ping',
    description: 'Pulse sanity check'
)]
class PingCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Pulse is alive.</info>');

        return Command::SUCCESS;
    }
}
