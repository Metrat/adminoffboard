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
use OCP\AppFramework\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * OCC command to offboard a user
 */
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
                'remote-wipe',
                'w',
                InputOption::VALUE_NONE,
                'Perform remote wipe on all devices'
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
                'f',
                InputOption::VALUE_NONE,
                'Skip confirmation prompt'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userId = $input->getOption('user');
        if (!$userId) {
            $output->writeln('<error>User ID is required. Use --user option.</error>');
            return 1;
        }

        // Check if user exists
        if (!$this->adapter->userExists($userId)) {
            $output->writeln("<error>User '$userId' does not exist.</error>");
            return 1;
        }

        $remoteWipe = (bool)$input->getOption('remote-wipe');
        $dryRun = (bool)$input->getOption('dry-run');
        $queue = (bool)$input->getOption('queue');
        $force = (bool)$input->getOption('force');

        // Show what will be done
        $output->writeln('<info>Offboarding user: ' . $userId . '</info>');
        $output->writeln('  - Disable user account');
        $output->writeln('  - Delete all device tokens');
        if ($remoteWipe) {
            $output->writeln('  - Remote wipe all devices');
        }
        if ($dryRun) {
            $output->writeln('<comment>DRY RUN MODE: No changes will be made</comment>');
        }
        if ($queue) {
            $output->writeln('<comment>Operation will be queued for background processing</comment>');
        }

        // Confirm
        if (!$force && !$dryRun) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                '<question>Proceed with offboarding? (y/N) </question>',
                false
            );
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Operation cancelled.</comment>');
                return 0;
            }
        }

        // Queue the operation if requested
        if ($queue) {
            $job = $this->queueManager->createOffboardJob(
                $userId,
                $remoteWipe,
                'occ',
                $remoteWipe ? 10 : 5 // Higher priority for remote wipe
            );
            
            $output->writeln('<info>Job queued successfully. Job ID: ' . $job->getId() . '</info>');
            return 0;
        }

        // Perform offboard
        try {
            if ($dryRun) {
                $output->writeln('<comment>DRY RUN: User would be disabled</comment>');
                $output->writeln('<comment>DRY RUN: All tokens would be deleted</comment>');
                if ($remoteWipe) {
                    $output->writeln('<comment>DRY RUN: Remote wipe would be performed</comment>');
                }
                
                // Log dry run audit
                $this->auditLogger->log(
                    AuditLogger::ACTION_OFFBOARD,
                    $userId,
                    'occ',
                    ['dry_run' => true, 'remote_wipe' => $remoteWipe],
                    AuditLogger::STATUS_SUCCESS
                );
                
                $output->writeln('<info>Dry run completed successfully.</info>');
                return 0;
            }

            // 1. Disable user
            $disabled = $this->adapter->disableUser($userId);
            if ($disabled) {
                $output->writeln('<info>✓ User disabled</info>');
            } else {
                $output->writeln('<error>✗ Failed to disable user</error>');
                return 1;
            }

            // 2. Delete all tokens
            $tokensDeleted = $this->adapter->deleteAllTokens($userId);
            $output->writeln("<info>✓ All tokens deleted</info>");

            // 3. Remote wipe if requested
            if ($remoteWipe) {
                $wipeResult = $this->adapter->remoteWipeUser($userId);
                if ($wipeResult) {
                    $output->writeln('<info>✓ Remote wipe initiated</info>');
                } else {
                    $output->writeln('<comment>⚠ Remote wipe not supported or failed</comment>');
                }
            }

            // Log audit
            $this->auditLogger->log(
                AuditLogger::ACTION_OFFBOARD,
                $userId,
                'occ',
                ['remote_wipe' => $remoteWipe, 'tokens_deleted' => $tokensDeleted],
                AuditLogger::STATUS_SUCCESS
            );

            $output->writeln('<info>✓ User offboarded successfully</info>');
            return 0;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            
            // Log failure
            $this->auditLogger->log(
                AuditLogger::ACTION_OFFBOARD,
                $userId,
                'occ',
                ['error' => $e->getMessage()],
                AuditLogger::STATUS_FAILURE
            );
            
            return 1;
        }
    }
}