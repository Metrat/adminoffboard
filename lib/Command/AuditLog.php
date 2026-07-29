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

use OCA\AdminOffboard\Audit\AuditLogger;
use OCA\AdminOffboard\Configuration\AppConfig;
use OCA\AdminOffboard\Db\Repository\AuditLogRepository;
use OCA\AdminOffboard\Queue\QueueManager;
use OCP\AppFramework\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * OCC command to clean up old audit logs
 */
class CleanupAuditLogs extends Command
{
    public function __construct(
        private AuditLogRepository $repository,
        private AuditLogger $auditLogger,
        private AppConfig $config,
        private QueueManager $queueManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('adminoffboard:cleanup-audit')
            ->setDescription('Clean up old audit logs')
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_REQUIRED,
                'Retention period in days (default: from config)',
                null
            )
            ->addOption(
                'dry-run',
                'r',
                InputOption::VALUE_NONE,
                'Simulate the operation without deleting'
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
        $days = $input->getOption('days') ?? $this->config->getAuditLogRetentionDays();
        $dryRun = (bool)$input->getOption('dry-run');
        $force = (bool)$input->getOption('force');

        $cutoff = time() - ($days * 24 * 60 * 60);
        
        // Count logs to delete
        $logsToDelete = $this->repository->findByDateRange(0, $cutoff, 10000);
        $count = count($logsToDelete);

        $output->writeln("<info>Audit Log Cleanup</info>");
        $output->writeln("===================");
        $output->writeln("");
        $output->writeln("Retention period: $days days");
        $output->writeln("Cutoff date: " . date('Y-m-d H:i:s', $cutoff));
        $output->writeln("Logs to delete: $count");
        
        if ($dryRun) {
            $output->writeln("\n<comment>DRY RUN MODE: No logs will be deleted</comment>");
            
            // Show sample of logs to delete
            if ($count > 0) {
                $output->writeln("\n<comment>Sample of logs to be deleted:</comment>");
                $sample = array_slice($logsToDelete, 0, 5);
                foreach ($sample as $log) {
                    $output->writeln(
                        "  - {$log->getAction()} | {$log->getUserId()} | " .
                        date('Y-m-d H:i:s', $log->getTimestamp())
                    );
                }
                if ($count > 5) {
                    $output->writeln("  ... and " . ($count - 5) . " more");
                }
            }
            
            return 0;
        }

        if ($count === 0) {
            $output->writeln("\n<comment>No logs to delete.</comment>");
            return 0;
        }

        // Confirm
        if (!$force) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                "\n<question>Delete $count old audit logs? (y/N) </question>",
                false
            );
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Operation cancelled.</comment>');
                return 0;
            }
        }

        // Perform cleanup
        try {
            $output->writeln("\n<info>Deleting old audit logs...</info>");
            
            $deleted = $this->repository->deleteOldLogs($days);
            
            $output->writeln("<info>✓ Deleted $deleted audit logs</info>");

            // Log the cleanup
            $this->auditLogger->log(
                AuditLogger::ACTION_CONFIG_CHANGE,
                'system',
                'occ',
                [
                    'action' => 'cleanup_audit_logs',
                    'days' => $days,
                    'deleted' => $deleted
                ],
                AuditLogger::STATUS_SUCCESS
            );

            // Also clean up old queue jobs
            $queueDeleted = $this->queueManager->cleanupOldJobs();
            $output->writeln("<info>✓ Cleaned up $queueDeleted old queue jobs</info>");

            $output->writeln("\n<info>Cleanup completed successfully.</info>");
            return 0;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            
            // Log failure
            $this->auditLogger->log(
                AuditLogger::ACTION_CONFIG_CHANGE,
                'system',
                'occ',
                [
                    'action' => 'cleanup_audit_logs',
                    'error' => $e->getMessage()
                ],
                AuditLogger::STATUS_FAILURE
            );
            
            return 1;
        }
    }
}