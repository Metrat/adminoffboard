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
use OCA\AdminOffboard\Audit\AuditLogger;
use OCA\AdminOffboard\Queue\QueueManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OffboardUser extends Command
{
    public function __construct(
        private NextcloudAdapter $adapter,
        private AuditLogger $auditLogger,
        private QueueManager $queueManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('adminoffboard:offboard')
            ->setDescription('Offboard a user (disable, delete tokens, optional remote wipe)')
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'User ID to offboard'
            )
            ->addOption(
                'disable',
                'd',
                InputOption::VALUE_NONE,
                'Disable the user account'
            )
            ->addOption(
                'delete-tokens',
                't',
                InputOption::VALUE_NONE,
                'Delete all authentication tokens'
            )
            ->addOption(
                'remote-wipe',
                'w',
                InputOption::VALUE_NONE,
                'Trigger remote wipe for all devices'
            )
            ->addOption(
                'queue',
                null,
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
        if (!$userId) {
            $output->writeln('<error>User ID is required (use --user option)</error>');
            return Command::INVALID;
        }

        $disable = $input->getOption('disable');
        $deleteTokens = $input->getOption('delete-tokens');
        $remoteWipe = $input->getOption('remote-wipe');
        $queue = $input->getOption('queue');
        $force = $input->getOption('force');

        try {
            if ($queue) {
                $jobId = $this->queueManager->addToQueue(
                    $userId,
                    $disable,
                    $deleteTokens,
                    $remoteWipe
                );
                $output->writeln(sprintf(
                    '<info>User %s added to offboarding queue with job ID: %d</info>',
                    $userId,
                    $jobId
                ));
                return Command::SUCCESS;
            }

            if (!$force) {
                $output->writeln(sprintf('<comment>WARNING: This will offboard user: %s</comment>', $userId));
                $output->writeln('<comment>Use --force to skip confirmation</comment>');
                return Command::SUCCESS;
            }

            if ($disable) {
                $this->adapter->disableUser($userId);
                $output->writeln(sprintf('<info>User %s disabled</info>', $userId));
                
            }

            if ($deleteTokens) {
                $count = $this->adapter->deleteAllUserTokens($userId);
                $output->writeln(sprintf(
                    '<info>Deleted %d tokens for user %s</info>',
                    $count,
                    $userId
                ));
                
            }

            if ($remoteWipe) {
                $count = $this->adapter->remoteWipeUserDevices($userId);
                $output->writeln(sprintf(
                    '<info>Remote wipe triggered for %d devices of user %s</info>',
                    $count,
                    $userId
                ));
                
            }

            if (!$disable && !$deleteTokens && !$remoteWipe) {
                $output->writeln('<error>No action specified. Use --disable, --delete-tokens, or --remote-wipe</error>');
                return Command::INVALID;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error offboarding user: ' . $e->getMessage() . '</error>');
            
            return Command::FAILURE;
        }
    }
}