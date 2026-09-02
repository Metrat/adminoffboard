<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('adminoffboard:test')
            ->setDescription('Test command for Admin Offboard');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('✅ Admin Offboard test command works!');
        return 0;
    }
}