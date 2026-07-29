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

use OCA\AdminOffboard\Queue\QueueManager;
use OCP\AppFramework\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * OCC command to process the queue
 */
class ProcessQueue extends Command
{
    public function __construct(
        private QueueManager $queueManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('adminoffboard:process-queue')
            ->setDescription('Process jobs from the queue')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Maximum number of jobs to process (default: unlimited)',
                null
            )
            ->addOption(
                'batch',
                'b',
                InputOption::VALUE_REQUIRED,
                'Number of jobs per batch (default: 10)',
                10
            )
            ->addOption(
                'recover',
                'r',
                InputOption::VALUE_NONE,
                'Recover stale jobs before processing'
            )
            ->addOption(
                'once',
                'o',
                InputOption::VALUE_NONE,
                'Process only one job and exit'
            )
            ->addOption(
                'watch',
                'w',
                InputOption::VALUE_NONE,
                'Watch mode - continuously process jobs'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $input->getOption('limit');
        $batchSize = (int)$input->getOption('batch');
        $recover = (bool)$input->getOption('recover');
        $once = (bool)$input->getOption('once');
        $watch = (bool)$input->getOption('watch');

        // Show queue stats
        $stats = $this->queueManager->getStats();
        $output->writeln('<info>Queue Status:</info>');
        $output->writeln("  Pending: {$stats['pending']}");
        $output->writeln("  Processing: {$stats['processing']}");
        $output->writeln("  Completed: {$stats['completed']}");
        $output->writeln("  Failed: {$stats['failed']}");
        $output->writeln("  Cancelled: {$stats['cancelled']}");

        // Recover stale jobs
        if ($recover) {
            $output->writeln("\n<info>Recovering stale jobs...</info>");
            $recovered = $this->queueManager->recoverStaleJobs();
            $output->writeln("<info>Recovered $recovered stale jobs</info>");
        }

        // Process once
        if ($once) {
            $output->writeln("\n<info>Processing one job...</info>");
            $processed = $this->queueManager->processNextJob();
            if ($processed) {
                $output->writeln('<info>✓ Job processed successfully</info>');
            } else {
                $output->writeln('<comment>No pending jobs found</comment>');
            }
            
            // Show updated stats
            $stats = $this->queueManager->getStats();
            $output->writeln("\n<info>Updated Queue Status:</info>");
            $output->writeln("  Pending: {$stats['pending']}");
            $output->writeln("  Processing: {$stats['processing']}");
            
            return 0;
        }

        // Watch mode
        if ($watch) {
            $output->writeln("\n<info>Watch mode enabled. Processing jobs continuously...</info>");
            $output->writeln("<comment>Press Ctrl+C to stop</comment>");
            
            $iteration = 0;
            while (true) {
                $iteration++;
                $processed = $this->queueManager->processJobs($batchSize);
                
                if ($processed === 0) {
                    $output->writeln("<comment>No pending jobs. Waiting 10 seconds...</comment>");
                    sleep(10);
                    continue;
                }
                
                $output->writeln("<info>Processed $processed jobs in batch $iteration</info>");
                
                // Check if we've reached the limit
                if ($limit !== null && $iteration >= (int)$limit) {
                    $output->writeln("<comment>Reached limit of $limit iterations. Stopping.</comment>");
                    break;
                }
                
                // Small delay between batches
                usleep(100000); // 0.1 seconds
            }
            
            return 0;
        }

        // Normal processing
        $output->writeln("\n<info>Processing queue...</info>");
        
        $maxJobs = $limit !== null ? (int)$limit : 0;
        $processed = $this->queueManager->processAll($maxJobs);
        
        $output->writeln("<info>✓ Processed $processed jobs</info>");

        // Show updated stats
        $stats = $this->queueManager->getStats();
        $output->writeln("\n<info>Updated Queue Status:</info>");
        $output->writeln("  Pending: {$stats['pending']}");
        $output->writeln("  Processing: {$stats['processing']}");
        $output->writeln("  Completed: {$stats['completed']}");
        $output->writeln("  Failed: {$stats['failed']}");

        return 0;
    }
}