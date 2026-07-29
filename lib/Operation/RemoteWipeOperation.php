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

namespace OCA\AdminOffboard\Operation;

use OCA\AdminOffboard\Adapter\NextcloudAdapter;
use OCA\AdminOffboard\Driver\DriverFactory;
use OCA\AdminOffboard\Queue\QueueManager;
use OCA\AdminOffboard\Exception\OperationFailedException;
use OCA\AdminOffboard\Audit\AuditLogger;

/**
 * Remote wipe operation
 */
class RemoteWipeOperation extends BaseOperation
{
    public function __construct(
        private NextcloudAdapter $adapter,
        private DriverFactory $driverFactory,
        private QueueManager $queueManager,
        AppLogger $logger,
        AuditLogger $auditLogger
    ) {
        parent::__construct($logger, $auditLogger);
    }

    public function getName(): string
    {
        return 'remote_wipe';
    }

    public function getDescription(): string
    {
        return 'Perform remote wipe on user devices';
    }

    public function getRequiredParams(): array
    {
        return ['user_id'];
    }

    public function getOptionalParams(): array
    {
        return [
            'device_id' => null,
            'dry_run' => false,
            'queue' => false,
            'actor' => 'system'
        ];
    }

    public function validateContext(array $context): bool
    {
        if (!isset($context['user_id']) || !is_string($context['user_id'])) {
            return false;
        }
        return !empty($context['user_id']);
    }

    public function estimateImpact(array $context): int
    {
        try {
            $devices = $this->adapter->getUserDevices($context['user_id']);
            return count($devices);
        } catch (\Exception $e) {
            return 1;
        }
    }

    public function execute(array $context): array
    {
        $this->logStart($this->getName(), $context);

        $userId = $context['user_id'];
        $deviceId = $context['device_id'] ?? null;
        $dryRun = $context['dry_run'] ?? false;
        $queue = $context['queue'] ?? false;
        $actor = $context['actor'] ?? 'system';

        try {
            // Get user devices
            $devices = $this->adapter->getUserDevices($userId);
            if (empty($devices)) {
                throw new OperationFailedException("No devices found for user: $userId");
            }

            // Filter devices if specific device requested
            if ($deviceId) {
                $devices = array_filter($devices, function ($device) use ($deviceId) {
                    return (string)$device->getId() === (string)$deviceId;
                });
                if (empty($devices)) {
                    throw new OperationFailedException("Device $deviceId not found for user: $userId");
                }
            }

            if ($queue) {
                $job = $this->queueManager->createRemoteWipeJob($userId, $deviceId, $actor);
                $result = [
                    'status' => 'queued',
                    'job_id' => $job->getId(),
                    'user_id' => $userId,
                    'device_count' => count($devices)
                ];
                $this->logComplete($this->getName(), $result);
                return $result;
            }

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
                $result = [
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
                $this->logComplete($this->getName(), $result);
                return $result;
            }

            // Execute wipe
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
                    if (!$this->driverFactory->supportsRemoteWipe($deviceData)) {
                        $unsupportedCount++;
                        $results[$device->getId()] = [
                            'status' => 'unsupported',
                            'message' => 'Device does not support remote wipe'
                        ];
                        continue;
                    }

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

            $result = [
                'status' => 'completed',
                'user_id' => $userId,
                'total' => count($devices),
                'success' => $successCount,
                'failed' => $failCount,
                'unsupported' => $unsupportedCount,
                'results' => $results
            ];

            $this->logComplete($this->getName(), $result);
            return $result;

        } catch (\Exception $e) {
            $this->logError($this->getName(), $e, $context);
            throw new OperationFailedException(
                "Remote wipe failed for user $userId: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}