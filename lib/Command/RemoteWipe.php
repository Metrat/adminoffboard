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
use OCA\AdminOffboard\Driver\DriverFactory;
use OCA\AdminOffboard\Queue\QueueManager;
use OCP\AppFramework\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * OCC command to perform remote wipe on devices
 */
class RemoteWipe extends Command
{
    public function __construct(
        private NextcloudAdapter $adapter,
        private DriverFactory $driverFactory,
        private AuditLogger $auditLogger,
        private QueueManager $queueManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('adminoffboard:remote-wipe')
            ->setDescription('Perform remote wipe on user devices')
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'User ID for remote wipe'
            )
            ->addOption(
                'device',
                'd',
                InputOption::VALUE_REQUIRED,
                'Specific device ID to wipe (optional)'
            )
            ->addOption(
                'dry-run',
                'r',
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

        $deviceId = $input->getOption('device');
        $dryRun = (bool)$input->getOption('dry-run');
        $queue = (bool)$input->getOption('queue');
        $force = (bool)$input->getOption('force');

        // Get user devices
        $devices = $this->adapter->getUserDevices($userId);
        
        if (empty($devices)) {
            $output->writeln("<comment>No devices found for user '$userId'</comment>");
            return 0;
        }

        // Filter devices
        if ($deviceId) {
            $devices = array_filter($devices, function ($device) use ($deviceId) {
                return (string)$device->getId() === (string)$deviceId;
            });
            
            if (empty($devices)) {
                $output->writeln("<error>Device '$deviceId' not found for user '$userId'</error>");
                return 1;
            }
        }

        // Show devices
        $output->writeln("<info>Found " . count($devices) . " devices for user '$userId'</info>");
        $output->writeln("\n<info>Devices:</info>");
        foreach ($devices as $device) {
            $supported = $device->isWipeSupported() ? '✓' : '✗';
            $active = $device->isActive() ? 'Active' : 'Inactive';
            $output->writeln("  [$supported] Device #{$device->getId()}: {$device->getDeviceName()} ($active)");
        }

        if ($dryRun) {
            $output->writeln("\n<comment>DRY RUN MODE: No changes will be made</comment>");
        }
        if ($queue) {
            $output->writeln("<comment>Operation will be queued for background processing</comment>");
        }

        // Confirm
        if (!$force && !$dryRun) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                "\n<question>Proceed with remote wipe? (y/N) </question>",
                false
            );
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('<comment>Operation cancelled.</comment>');
                return 0;
            }
        }

        // Queue the operation if requested
        if ($queue) {
            $job = $this->queueManager->createRemoteWipeJob(
                $userId,
                $deviceId,
                'occ'
            );
            
            $output->writeln("\n<info>Job queued successfully. Job ID: " . $job->getId() . "</info>");
            return 0;
        }

        // Perform remote wipe
        try {
            if ($dryRun) {
                foreach ($devices as $device) {
                    $output->writeln("<comment>DRY RUN: Would wipe device #{$device->getId()}: {$device->getDeviceName()}</comment>");
                }
                
                // Log dry run audit
                $this->auditLogger->log(
                    AuditLogger::ACTION_REMOTE_WIPE,
                    $userId,
                    'occ',
                    ['dry_run' => true, 'device_id' => $deviceId, 'device_count' => count($devices)],
                    AuditLogger::STATUS_SUCCESS
                );
                
                $output->writeln("\n<info>Dry run completed successfully.</info>");
                return 0;
            }

            $successCount = 0;
            $failCount = 0;
            $unsupportedCount = 0;

            foreach ($devices as $device) {
                if (!$device->isWipeSupported()) {
                    $unsupportedCount++;
                    $output->writeln("<comment>⚠ Device #{$device->getId()} does not support remote wipe</comment>");
                    continue;
                }

                try {
                    $result = $this->adapter->remoteWipeDevice($device->getId());
                    
                    if ($result) {
                        $successCount++;
                        $output->writeln("<info>✓ Device #{$device->getId()} wiped successfully</info>");
                        
                        $this->auditLogger->log(
                            AuditLogger::ACTION_REMOTE_WIPE,
                            $userId,
                            'occ',
                            ['device_id' => $device->getId(), 'device_name' => $device->getDeviceName()],
                            AuditLogger::STATUS_SUCCESS
                        );
                    } else {
                        $failCount++;
                        $output->writeln("<error>✗ Failed to wipe device #{$device->getId()}</error>");
                        
                        $this->auditLogger->log(
                            AuditLogger::ACTION_REMOTE_WIPE,
                            $userId,
                            'occ',
                            ['device_id' => $device->getId(), 'error' => 'Wipe failed'],
                            AuditLogger::STATUS_FAILURE
                        );
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    $output->writeln("<error>✗ Error wiping device #{$device->getId()}: " . $e->getMessage() . "</error>");
                    
                    $this->auditLogger->log(
                        AuditLogger::ACTION_REMOTE_WIPE,
                        $userId,
                        'occ',
                        ['device_id' => $device->getId(), 'error' => $e->getMessage()],
                        AuditLogger::STATUS_FAILURE
                    );
                }
            }

            $output->writeln("\n<info>Summary:</info>");
            $output->writeln("  Success: $successCount");
            $output->writeln("  Failed: $failCount");
            $output->writeln("  Unsupported: $unsupportedCount");

            if ($failCount > 0) {
                return 1;
            }
            
            $output->writeln("\n<info>Remote wipe operations completed successfully.</info>");
            return 0;

        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            
            // Log failure
            $this->auditLogger->log(
                AuditLogger::ACTION_REMOTE_WIPE,
                $userId,
                'occ',
                ['error' => $e->getMessage()],
                AuditLogger::STATUS_FAILURE
            );
            
            return 1;
        }
    }
}