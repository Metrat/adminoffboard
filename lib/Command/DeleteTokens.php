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

class DeleteTokens extends Command
{
    public function __construct(
        private NextcloudAdapter $adapter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('adminoffboard:tokens:delete')
            ->setDescription('Delete authentication tokens for users')
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'User ID to delete tokens for'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Delete all tokens for specified user'
            )
            ->addOption(
                'except-current',
                null,
                InputOption::VALUE_NONE,
                'Delete all tokens except the current session'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userId = $input->getOption('user');
        $all = $input->getOption('all');
        $exceptCurrent = $input->getOption('except-current');

        if (!$userId) {
            $output->writeln('<error>User ID is required (use --user option)</error>');
            return Command::INVALID;
        }

        try {
            if ($all) {
                $count = $this->adapter->deleteAllUserTokens($userId);
                $output->writeln(sprintf(
                    '<info>Deleted all %d tokens for user %s</info>',
                    $count,
                    $userId
                ));
            } elseif ($exceptCurrent) {
                $count = $this->adapter->deleteUserTokensExceptCurrent($userId);
                $output->writeln(sprintf(
                    '<info>Deleted %d tokens for user %s (except current session)</info>',
                    $count,
                    $userId
                ));
            } else {
                $output->writeln('<error>Please specify either --all or --except-current option</error>');
                return Command::INVALID;
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error deleting tokens: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}