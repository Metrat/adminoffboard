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
 * OCC command to delete tokens for users
 */
class DeleteTokens extends Command
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
            ->setName('adminoffboard:delete-tokens')
            ->setDescription('Delete all device tokens for multiple users')
            ->addOption(
                'users',
                'u',
                InputOption::VALUE_REQUIRED,
                'Comma-separated list of user IDs'
            )
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'File containing user IDs (one per line)'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Delete tokens for all users'
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
        // Get user list
        $userIds = $this->getUserList($input, $output);
        if (empty($userIds)) {
            $output->writeln('<error>No users specified. Use --users, --file, or --all option.</error>');
            return 1;
        }

        $dryRun = (bool)$input->getOption('dry-run');
        $queue = (bool)$input->getOption('queue');
        $force = (bool)$input->getOption('force');

        // Validate users
        $validUsers = [];
        $invalidUsers = [];
        foreach ($userIds as $userId) {
            if ($this->adapter->userExists($userId)) {
                $validUsers[] = $userId;
            } else {
                $invalidUsers[] = $userId;
            }
        }

        if (empty($validUsers)) {
            $output->writeln('<error>No valid users found.</error>');
            return 1;
        }

        // Show summary
        $output->writeln('<info>Deleting tokens for ' . count($validUsers) . ' users</info>');
        if (!empty($invalidUsers)) {
            $output->writeln('<comment>Skipping ' . count($invalidUsers) . ' invalid users</comment>');
        }
        if ($dryRun) {
            $output->writeln('<comment>DRY RUN MODE: No changes will be made</comment>');
        }
        if ($queue) {
            $output->writeln('<comment>Operation will be queued for background processing</comment>');
        }

        // Show users
        $output->writeln("\n<info>Users to process:</info>");
        foreach ($validUsers as $userId) {
            $output->writeln("  - $userId");
        }
        $output->writeln('');

        // Confirm
        if (!$force && !$dryRun) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                '<question>Proceed with deleting tokens for ' . count($validUsers) . ' users? (y/N) </question>',
                false
            );
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Operation cancelled.</comment>');
                return 0;
            }
        }

        // Queue the operation if requested
        if ($queue) {
            $job = $this->queueManager->createDeleteTokensJob(
                $validUsers,
                'occ'
            );
            
            $output->writeln('<info>Job queued successfully. Job ID: ' . $job->getId() . '</info>');
            return 0;
        }

        // Perform deletion
        try {
            if ($dryRun) {
                foreach ($validUsers as $userId) {
                    $tokens = $this->adapter->deleteAllTokens($userId);
                    $output->writeln("<comment>DRY RUN: Tokens for user '$userId' would be deleted</comment>");
                }
                
                // Log dry run audit
                foreach ($validUsers as $userId) {
                    $this->auditLogger->log(
                        AuditLogger::ACTION_DELETE_TOKENS,
                        $userId,
                        'occ',
                        ['dry_run' => true],
                        AuditLogger::STATUS_SUCCESS
                    );
                }
                
                $output->writeln('<info>Dry run completed successfully.</info>');
                return 0;
            }

            $successCount = 0;
            $failCount = 0;
            $totalTokensDeleted = 0;

            foreach ($validUsers as $userId) {
                try {
                    $deleted = $this->adapter->deleteAllTokens($userId);
                    if ($deleted) {
                        $successCount++;
                        $totalTokensDeleted++;
                        $output->writeln("<info>✓ Tokens deleted for user '$userId'</info>");
                        
                        $this->auditLogger->log(
                            AuditLogger::ACTION_DELETE_TOKENS,
                            $userId,
                            'occ',
                            ['tokens_deleted' => true],
                            AuditLogger::STATUS_SUCCESS
                        );
                    } else {
                        $failCount++;
                        $output->writeln("<error>✗ Failed to delete tokens for user '$userId'</error>");
                        
                        $this->auditLogger->log(
                            AuditLogger::ACTION_DELETE_TOKENS,
                            $userId,
                            'occ',
                            ['error' => 'Failed to delete tokens'],
                            AuditLogger::STATUS_FAILURE
                        );
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    $output->writeln("<error>✗ Error deleting tokens for user '$userId': " . $e->getMessage() . "</error>");
                    
                    $this->auditLogger->log(
                        AuditLogger::ACTION_DELETE_TOKENS,
                        $userId,
                        'occ',
                        ['error' => $e->getMessage()],
                        AuditLogger::STATUS_FAILURE
                    );
                }
            }

            $output->writeln("\n<info>Summary:</info>");
            $output->writeln("  Success: $successCount");
            $output->writeln("  Failed: $failCount");
            $output->writeln("  Total tokens deleted: $totalTokensDeleted");

            if ($failCount > 0) {
                return 1;
            }
            
            $output->writeln('<info>All tokens deleted successfully.</info>');
            return 0;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }

    /**
     * Get user list from input
     */
    private function getUserList(InputInterface $input, OutputInterface $output): array
    {
        $users = [];

        // Get from --all option
        if ($input->getOption('all')) {
            $allUsers = $this->adapter->getAllUsers();
            foreach ($allUsers as $user) {
                $users[] = $user->getUID();
            }
            return $users;
        }

        // Get from --users option
        $usersOption = $input->getOption('users');
        if ($usersOption) {
            $users = array_merge($users, explode(',', $usersOption));
        }

        // Get from --file option
        $fileOption = $input->getOption('file');
        if ($fileOption) {
            if (!file_exists($fileOption)) {
                $output->writeln("<error>File not found: $fileOption</error>");
                return [];
            }
            
            $content = file_get_contents($fileOption);
            $lines = explode("\n", $content);
            $users = array_merge($users, array_filter(array_map('trim', $lines)));
        }

        // Remove duplicates and empty values
        $users = array_unique(array_filter($users));
        
        return $users;
    }
}