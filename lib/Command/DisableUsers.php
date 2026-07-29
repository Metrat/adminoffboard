<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Metrat <disparam@gmail.com>
 *
 * @author Metrat <disparam@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\AdminOffboard\Command;

use OCA\AdminOffboard\Adapter\NextcloudAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DisableUsers extends Command
{
    public function __construct(
        private NextcloudAdapter $adapter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('adminoffboard:users:disable')
            ->setDescription('Disable user accounts')
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'User ID to disable'
            )
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'File containing user IDs (one per line)'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Simulate the operation without making changes'
            )
            ->addOption(
                'queue',
                'q',
                InputOption::VALUE_NONE,
                'Queue the operation for background processing'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Skip confirmation prompt'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userId = $input->getOption('user');
        $file = $input->getOption('file');
        $dryRun = $input->getOption('dry-run');
        $queue = $input->getOption('queue');
        $force = $input->getOption('force');

        try {
            if ($userId) {
                if (!$force) {
                    $output->writeln(sprintf('<comment>WARNING: This will disable user: %s</comment>', $userId));
                    $output->writeln('<comment>Use --force to skip confirmation</comment>');
                    return Command::SUCCESS;
                }
                if ($dryRun) {
                    $output->writeln(sprintf('<info>DRY RUN: Would disable user: %s</info>', $userId));
                } else {
                    $this->adapter->disableUser($userId);
                    $output->writeln(sprintf('<info>User %s disabled successfully</info>', $userId));
                }
            } elseif ($file) {
                if (!file_exists($file)) {
                    $output->writeln('<error>File not found: ' . $file . '</error>');
                    return Command::FAILURE;
                }
                $users = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $count = 0;
                foreach ($users as $user) {
                    $user = trim($user);
                    if (!empty($user)) {
                        if ($dryRun) {
                            $output->writeln(sprintf('<info>DRY RUN: Would disable user: %s</info>', $user));
                        } else {
                            $this->adapter->disableUser($user);
                            $output->writeln(sprintf('<info>Disabled user: %s</info>', $user));
                        }
                        $count++;
                    }
                }
                $output->writeln(sprintf('<info>Successfully processed %d users from file</info>', $count));
            } else {
                $output->writeln('<error>Please specify --user or --file option</error>');
                return Command::INVALID;
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error disabling users: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}