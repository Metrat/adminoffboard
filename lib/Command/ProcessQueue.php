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

use OCA\AdminOffboard\Queue\JobProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessQueue extends Command
{
    public function __construct(
        private JobProcessor $jobProcessor
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('adminoffboard:process-queue')
            ->setDescription('Process pending offboarding queue jobs')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum number of jobs to process',
                10
            )
            ->addOption(
                'job-id',
                'j',
                InputOption::VALUE_OPTIONAL,
                'Process specific job ID'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = (int) $input->getOption('limit');
        $jobId = $input->getOption('job-id');

        try {
            if ($jobId) {
                $this->jobProcessor->processJob((int) $jobId);
                $output->writeln(sprintf('<info>Job %d processed successfully</info>', $jobId));
            } else {
                $processed = $this->jobProcessor->processPendingJobs($limit);
                $output->writeln(sprintf(
                    '<info>Processed %d pending jobs</info>',
                    $processed
                ));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error processing queue: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}