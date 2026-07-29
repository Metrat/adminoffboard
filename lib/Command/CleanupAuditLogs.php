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
use OCA\AdminOffboard\Db\Repository\JobRepository;
use OCP\AppFramework\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * OCC command to clean up old audit logs and jobs
 */
class CleanupAuditLogs extends Command
{
    public function __construct(
        private AuditLogRepository $auditLogRepository,
        private JobRepository $jobRepository,
        private AuditLogger $auditLogger,
        private AppConfig $config
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('adminoffboard:cleanup')
            ->setDescription('Clean up old audit logs and completed jobs')
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_REQUIRED,
                'Retention period in days for audit logs (default: from config)',
                null
            )
            ->addOption(
                'job-days',
                'j',
                InputOption::VALUE_REQUIRED,
                'Retention period in days for completed jobs (default: 30)',
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
        $auditDays = (int)($input->getOption('days') ?? $this->config->getAuditLogRetentionDays());
        $jobDays = (int)($input->getOption('job-days') ?? 30);
        $dryRun = (bool)$input->getOption('dry-run');
        $force = (bool)$input->getOption('force');

        $output->writeln('<info>AdminOffboard Cleanup</info>');
        $output->writeln('========================');
        $output->writeln('');

        // Count items to delete
        $cutoffAudit = time() - ($auditDays * 24 * 60 * 60);
        $cutoffJobs = time() - ($jobDays * 24 * 60 * 60);

        $auditCount = $this->auditLogRepository->countByDateRange(0, $cutoffAudit);
        $jobCount = $this->jobRepository->countOldCompleted($cutoffJobs);

        $output->writeln('<info>Items to clean up:</info>');
        $output->writeln("  Audit logs older than {$auditDays} days: {$auditCount}");
        $output->writeln("  Completed jobs older than {$jobDays} days: {$jobCount}");

        if ($dryRun) {
            $output->writeln("\n<comment>DRY RUN MODE: No items will be deleted</comment>");
            return 0;
        }

        if ($auditCount === 0 && $jobCount === 0) {
            $output->writeln("\n<comment>Nothing to clean up.</comment>");
            return 0;
        }

        // Confirm
        if (!$force) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                "\n<question>Delete {$auditCount} audit logs and {$jobCount} jobs? (y/N) </question>",
                false
            );
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Operation cancelled.</comment>');
                return 0;
            }
        }

        // Perform cleanup
        try {
            $output->writeln("\n<info>Cleaning up...</info>");

            // Delete old audit logs
            $deletedAudit = $this->auditLogRepository->deleteOldLogs($auditDays);
            $output->writeln("  ✓ Deleted {$deletedAudit} audit logs");

            // Delete old completed jobs
            $deletedJobs = $this->jobRepository->deleteOldCompleted($jobDays);
            $output->writeln("  ✓ Deleted {$deletedJobs} completed jobs");

            // Log the cleanup
            $this->auditLogger->log(
                AuditLogger::ACTION_CONFIG_CHANGE,
                'system',
                'occ',
                [
                    'action' => 'cleanup',
                    'audit_days' => $auditDays,
                    'job_days' => $jobDays,
                    'deleted_audit' => $deletedAudit,
                    'deleted_jobs' => $deletedJobs
                ],
                AuditLogger::STATUS_SUCCESS
            );

            $output->writeln("\n<info>Cleanup completed successfully.</info>");
            return 0;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');

            $this->auditLogger->log(
                AuditLogger::ACTION_CONFIG_CHANGE,
                'system',
                'occ',
                [
                    'action' => 'cleanup',
                    'error' => $e->getMessage()
                ],
                AuditLogger::STATUS_FAILURE
            );

            return 1;
        }
    }
}