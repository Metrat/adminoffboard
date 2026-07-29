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

use OCA\AdminOffboard\Audit\AuditCleanup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupAuditLogs extends Command
{
    public function __construct(
        private AuditCleanup $auditCleanup
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('adminoffboard:cleanup')
            ->setDescription('Clean up old audit log entries')
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Remove entries older than specified days',
                90
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be deleted without actually deleting'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int) $input->getOption('days');
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $output->writeln('<info>DRY RUN MODE - No actual deletion will occur</info>');
        }

        try {
            $count = $this->auditCleanup->cleanup($days, $dryRun);
            
            if ($dryRun) {
                $output->writeln(sprintf(
                    '<info>Would delete %d audit log entries older than %d days</info>',
                    $count,
                    $days
                ));
            } else {
                $output->writeln(sprintf(
                    '<info>Successfully deleted %d audit log entries older than %d days</info>',
                    $count,
                    $days
                ));
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error cleaning up audit logs: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}