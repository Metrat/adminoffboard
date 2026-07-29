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

namespace OCA\AdminOffboard\Service;

use OCA\AdminOffboard\Adapter\NextcloudAdapter;
use OCA\AdminOffboard\Audit\AuditLogger;
use OCA\AdminOffboard\Queue\QueueManager;
use OCA\AdminOffboard\Driver\DriverFactory;
use OCA\AdminOffboard\Validator\UserValidator;
use OCA\AdminOffboard\Validator\DeviceValidator;
use OCA\AdminOffboard\Exception\ValidationException;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Service for remote wipe operations
 */
class RemoteWipeService
{
    public function __construct(
        private NextcloudAdapter $adapter,
        private DriverFactory $driverFactory,
        private AuditLogger $auditLogger,
        private QueueManager $queueManager,
        private UserValidator $userValidator,
        private DeviceValidator $deviceValidator,
        private AppLogger $logger
    ) {
    }

    /**
     * Perform remote wipe for a user
     */
    public function wipeUser(
        string $userId,
        ?string $deviceId = null,
        bool $dryRun = false,
        bool $queue = false,
        string $actor = 'system'
    ): array {
        $this->logger->info('Remote wipe for user', [
            'user_id' => $userId,
            'device_id' => $deviceId,
            'dry_run' => $dryRun,
            'queue' => $queue,
            'actor' => $actor
        ]);

        // Validate user
        $this->userValidator->validateUserExists($userId);

        // Get devices
        $devices = $this->adapter->getUserDevices($userId);
        if (empty($devices)) {
            throw new ValidationException("No devices found for user: $userId");
        }

        // Filter devices if specific device requested
        if ($deviceId) {
            $devices = array_filter($devices, function ($device) use ($deviceId) {
                return (string)$device->getId() === (string)$deviceId;
            });
            if (empty($devices)) {
                throw new ValidationException("Device $deviceId not found for user: $userId");
            }
        }

        // Queue if requested
        if ($queue) {
            $job = $this->queueManager->createRemoteWipeJob($userId, $deviceId, $actor);
            return [
                'status' => 'queued',
                'job_id' => $job->getId(),
                'user_id' => $userId,
                'device_count' => count($devices)
            ];
        }

        // Dry run
        if ($dryRun) {
            foreach ($devices as $device) {
                $this->auditLogger->log(
                    AuditLogger::ACTION_REMOTE_WIPE,
                    $userId,
                    $actor,
                    [
                        'dry_run' => true,
                        'device_id' => $device->getId(),
                        'device_name' => $device->getDeviceName()
                    ],
                    AuditLogger::STATUS_SUCCESS
                );
            }
            return [
                'status' => 'dry_run',
                'user_id' => $userId,
                'devices' => array_map(function ($device) {
                    return [
                        'id' => $device->getId(),
                        'name' => $device->getDeviceName(),
                        'supported' => $device->isWipeSupported()
                    ];
                }, $devices)
            ];
        }

        // Execute
        $results = [];
        $successCount = 0;
        $failCount = 0;
        $unsupportedCount = 0;

        foreach ($devices as $device) {
            $deviceData = [
                'id' => $device->getId(),
                'name' => $device->getDeviceName(),
                'token_id' => $device->getTokenId(),
                'device_type' => $device->getDeviceType()
            ];

            try {
                // Check if device supports remote wipe
                if (!$this->driverFactory->supportsRemoteWipe($deviceData)) {
                    $unsupportedCount++;
                    $results[$device->getId()] = [
                        'status' => 'unsupported',
                        'message' => 'Device does not support remote wipe'
                    ];
                    continue;
                }

                // Perform wipe
                $wiped = $this->adapter->remoteWipeDevice($device->getId());

                if ($wiped) {
                    $successCount++;
                    $results[$device->getId()] = [
                        'status' => 'success',
                        'device_name' => $device->getDeviceName()
                    ];
                    $this->auditLogger->log(
                        AuditLogger::ACTION_REMOTE_WIPE,
                        $userId,
                        $actor,
                        [
                            'device_id' => $device->getId(),
                            'device_name' => $device->getDeviceName()
                        ],
                        AuditLogger::STATUS_SUCCESS
                    );
                } else {
                    $failCount++;
                    $results[$device->getId()] = [
                        'status' => 'failed',
                        'device_name' => $device->getDeviceName(),
                        'message' => 'Remote wipe failed'
                    ];
                    $this->auditLogger->log(
                        AuditLogger::ACTION_REMOTE_WIPE,
                        $userId,
                        $actor,
                        [
                            'device_id' => $device->getId(),
                            'device_name' => $device->getDeviceName(),
                            'error' => 'Wipe failed'
                        ],
                        AuditLogger::STATUS_FAILURE
                    );
                }
            } catch (\Exception $e) {
                $failCount++;
                $results[$device->getId()] = [
                    'status' => 'failed',
                    'device_name' => $device->getDeviceName(),
                    'message' => $e->getMessage()
                ];
                $this->auditLogger->log(
                    AuditLogger::ACTION_REMOTE_WIPE,
                    $userId,
                    $actor,
                    [
                        'device_id' => $device->getId(),
                        'error' => $e->getMessage()
                    ],
                    AuditLogger::STATUS_FAILURE
                );
            }
        }

        return [
            'status' => 'completed',
            'user_id' => $userId,
            'total' => count($devices),
            'success' => $successCount,
            'failed' => $failCount,
            'unsupported' => $unsupportedCount,
            'results' => $results
        ];
    }

    /**
     * Check if remote wipe is possible for a user
     */
    public function canWipeUser(string $userId): array
    {
        $devices = $this->adapter->getUserDevices($userId);
        $wipeable = [];

        foreach ($devices as $device) {
            $deviceData = [
                'id' => $device->getId(),
                'name' => $device->getDeviceName(),
                'token_id' => $device->getTokenId(),
                'device_type' => $device->getDeviceType()
            ];

            if ($this->driverFactory->supportsRemoteWipe($deviceData)) {
                $wipeable[] = [
                    'id' => $device->getId(),
                    'name' => $device->getDeviceName()
                ];
            }
        }

        return [
            'user_id' => $userId,
            'total_devices' => count($devices),
            'wipeable_devices' => $wipeable
        ];
    }
}