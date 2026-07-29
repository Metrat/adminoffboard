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
use OCA\AdminOffboard\Validator\UserValidator;
use OCA\AdminOffboard\Exception\ValidationException;
use OCA\AdminOffboard\Exception\OperationFailedException;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Service for user offboarding operations
 */
class OffboardService
{
    public function __construct(
        private NextcloudAdapter $adapter,
        private AuditLogger $auditLogger,
        private QueueManager $queueManager,
        private UserValidator $userValidator,
        private AppLogger $logger
    ) {
    }

    /**
     * Offboard a user
     */
    public function offboardUser(
        string $userId,
        bool $remoteWipe = false,
        bool $dryRun = false,
        bool $queue = false,
        string $actor = 'system'
    ): array {
        $this->logger->info('Offboarding user', [
            'user_id' => $userId,
            'remote_wipe' => $remoteWipe,
            'dry_run' => $dryRun,
            'queue' => $queue,
            'actor' => $actor
        ]);

        // Validate user
        $this->userValidator->validateUserExists($userId);
        $this->userValidator->validateNotSelf($userId, $actor);

        // Queue if requested
        if ($queue) {
            $job = $this->queueManager->createOffboardJob($userId, $remoteWipe, $actor);
            return [
                'status' => 'queued',
                'job_id' => $job->getId(),
                'user_id' => $userId
            ];
        }

        // Dry run
        if ($dryRun) {
            $this->auditLogger->log(
                AuditLogger::ACTION_OFFBOARD,
                $userId,
                $actor,
                ['dry_run' => true, 'remote_wipe' => $remoteWipe],
                AuditLogger::STATUS_SUCCESS
            );
            return [
                'status' => 'dry_run',
                'user_id' => $userId,
                'remote_wipe' => $remoteWipe
            ];
        }

        // Execute offboard
        $results = [];
        $success = true;

        try {
            // 1. Disable user
            $disabled = $this->adapter->disableUser($userId);
            $results['disabled'] = $disabled;
            if (!$disabled) {
                $success = false;
                throw new OperationFailedException("Failed to disable user: $userId");
            }

            // 2. Delete all tokens
            $tokensDeleted = $this->adapter->deleteAllTokens($userId);
            $results['tokens_deleted'] = $tokensDeleted;
            if (!$tokensDeleted) {
                $success = false;
                throw new OperationFailedException("Failed to delete tokens for user: $userId");
            }

            // 3. Remote wipe if requested
            if ($remoteWipe) {
                $wipeResult = $this->adapter->remoteWipeUser($userId);
                $results['remote_wipe'] = $wipeResult;
                if (!$wipeResult) {
                    $success = false;
                    throw new OperationFailedException("Remote wipe failed for user: $userId");
                }
            }

            // Log success
            $this->auditLogger->log(
                AuditLogger::ACTION_OFFBOARD,
                $userId,
                $actor,
                $results,
                AuditLogger::STATUS_SUCCESS
            );

            return [
                'status' => 'success',
                'user_id' => $userId,
                'results' => $results
            ];

        } catch (\Exception $e) {
            // Log failure
            $this->auditLogger->log(
                AuditLogger::ACTION_OFFBOARD,
                $userId,
                $actor,
                [
                    'error' => $e->getMessage(),
                    'results' => $results
                ],
                AuditLogger::STATUS_FAILURE
            );

            throw new OperationFailedException(
                "Offboard failed for user $userId: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Offboard multiple users
     */
    public function offboardUsers(
        array $userIds,
        bool $remoteWipe = false,
        bool $dryRun = false,
        bool $queue = false,
        string $actor = 'system',
        int $batchSize = 100
    ): array {
        $this->logger->info('Offboarding multiple users', [
            'user_count' => count($userIds),
            'remote_wipe' => $remoteWipe,
            'dry_run' => $dryRun,
            'queue' => $queue,
            'actor' => $actor
        ]);

        $results = [];
        $successCount = 0;
        $failCount = 0;

        // Queue if requested
        if ($queue) {
            // Split into batches
            $batches = array_chunk($userIds, $batchSize);
            $jobIds = [];

            foreach ($batches as $batch) {
                $job = $this->queueManager->createOffboardJob(
                    implode(',', $batch),
                    $remoteWipe,
                    $actor
                );
                $jobIds[] = $job->getId();
            }

            return [
                'status' => 'queued',
                'job_count' => count($jobIds),
                'job_ids' => $jobIds,
                'total_users' => count($userIds)
            ];
        }

        // Process each user
        foreach ($userIds as $userId) {
            try {
                $result = $this->offboardUser($userId, $remoteWipe, $dryRun, false, $actor);
                $results[$userId] = $result;
                $successCount++;
            } catch (\Exception $e) {
                $results[$userId] = [
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                $failCount++;
            }
        }

        return [
            'status' => 'completed',
            'total' => count($userIds),
            'success' => $successCount,
            'failed' => $failCount,
            'results' => $results
        ];
    }
}