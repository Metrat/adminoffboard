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

namespace OCA\AdminOffboard\Queue;

use OCA\AdminOffboard\Adapter\NextcloudAdapter;
use OCA\AdminOffboard\Audit\AuditLogger;
use OCA\AdminOffboard\Db\Entity\Job;
use OCA\AdminOffboard\Db\Repository\JobRepository;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Job processor - executes queued jobs
 */
class JobProcessor
{
    public function __construct(
        private JobRepository $jobRepository,
        private AuditLogger $auditLogger,
        private AppLogger $logger,
        private NextcloudAdapter $adapter
    ) {
    }

    /**
     * Process a single job
     */

    public function processPendingJobs(int $limit = 10): int
    {
        // Placeholder - needs queue implementation
        return 0;
    }
    public function processJob(Job $job): bool
    {
        $this->logger->info('Processing job', [
            'jobId' => $job->getId(),
            'type' => $job->getJobType()
        ]);

        try {
            $result = match ($job->getJobType()) {
                Job::TYPE_OFFBOARD => $this->processOffboardJob($job),
                Job::TYPE_DISABLE_USERS => $this->processDisableUsersJob($job),
                Job::TYPE_DELETE_TOKENS => $this->processDeleteTokensJob($job),
                Job::TYPE_REMOTE_WIPE => $this->processRemoteWipeJob($job),
                default => throw new \InvalidArgumentException(
                    'Unknown job type: ' . $job->getJobType()
                )
            };

            if ($result) {
                $this->jobRepository->update($job);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error('Job processing failed', [
                'jobId' => $job->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Process offboard job
     */
    private function processOffboardJob(Job $job): bool
    {
        $payload = $job->getPayload();
        $userId = $payload['user_id'] ?? null;

        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required for offboard job');
        }

        $this->logger->info('Starting offboard operation', [
            'jobId' => $job->getId(),
            'userId' => $userId
        ]);

        // 1. Disable user
        $disabled = $this->adapter->disableUser($userId);
        
        // 2. Delete all tokens
        $tokensDeleted = $this->adapter->deleteAllTokens($userId);
        
        // 3. Perform remote wipe if requested
        if ($payload['remote_wipe'] ?? false) {
            $this->adapter->remoteWipeUser($userId);
        }

        // Log the operation
        $this->auditLogger->log(
            AuditLogger::ACTION_OFFBOARD,
            $userId,
            $job->getCreatedBy() ?? 'system',
            ['remote_wipe' => $payload['remote_wipe'] ?? false],
            AuditLogger::STATUS_SUCCESS
        );

        return true;
    }

    /**
     * Process disable users job
     */
    private function processDisableUsersJob(Job $job): bool
    {
        $payload = $job->getPayload();
        $userIds = $payload['user_ids'] ?? [];

        if (empty($userIds)) {
            throw new \InvalidArgumentException('No user IDs provided for disable job');
        }

        $this->logger->info('Starting mass disable operation', [
            'jobId' => $job->getId(),
            'userCount' => count($userIds)
        ]);

        $results = [];
        foreach ($userIds as $userId) {
            try {
                $disabled = $this->adapter->disableUser($userId);
                $results[$userId] = $disabled;
                
                $this->auditLogger->log(
                    AuditLogger::ACTION_DISABLE_USERS,
                    $userId,
                    $job->getCreatedBy() ?? 'system',
                    ['status' => $disabled ? 'success' : 'failed'],
                    $disabled ? AuditLogger::STATUS_SUCCESS : AuditLogger::STATUS_FAILURE
                );
            } catch (\Exception $e) {
                $results[$userId] = false;
                $this->logger->error('Failed to disable user', [
                    'userId' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Update job with results
        $job->setPayload(array_merge($payload, [
            'results' => $results,
            'success_count' => count(array_filter($results))
        ]));

        return true;
    }

    /**
     * Process delete tokens job
     */
    private function processDeleteTokensJob(Job $job): bool
    {
        $payload = $job->getPayload();
        $userIds = $payload['user_ids'] ?? [];

        if (empty($userIds)) {
            throw new \InvalidArgumentException('No user IDs provided for delete tokens job');
        }

        $this->logger->info('Starting mass token deletion', [
            'jobId' => $job->getId(),
            'userCount' => count($userIds)
        ]);

        $results = [];
        foreach ($userIds as $userId) {
            try {
                $deleted = $this->adapter->deleteAllTokens($userId);
                $results[$userId] = $deleted;
                
                $this->auditLogger->log(
                    AuditLogger::ACTION_DELETE_TOKENS,
                    $userId,
                    $job->getCreatedBy() ?? 'system',
                    ['status' => $deleted ? 'success' : 'failed'],
                    $deleted ? AuditLogger::STATUS_SUCCESS : AuditLogger::STATUS_FAILURE
                );
            } catch (\Exception $e) {
                $results[$userId] = false;
                $this->logger->error('Failed to delete tokens', [
                    'userId' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Update job with results
        $job->setPayload(array_merge($payload, [
            'results' => $results,
            'success_count' => count(array_filter($results))
        ]));

        return true;
    }

    /**
     * Process remote wipe job
     */
    private function processRemoteWipeJob(Job $job): bool
    {
        $payload = $job->getPayload();
        $userId = $payload['user_id'] ?? null;
        $deviceId = $payload['device_id'] ?? null;

        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required for remote wipe job');
        }

        $this->logger->info('Starting remote wipe operation', [
            'jobId' => $job->getId(),
            'userId' => $userId,
            'deviceId' => $deviceId
        ]);

        $result = $this->adapter->remoteWipeUser($userId, $deviceId);

        $this->auditLogger->log(
            AuditLogger::ACTION_REMOTE_WIPE,
            $userId,
            $job->getCreatedBy() ?? 'system',
            ['device_id' => $deviceId, 'status' => $result ? 'success' : 'failed'],
            $result ? AuditLogger::STATUS_SUCCESS : AuditLogger::STATUS_FAILURE
        );

        return $result;
    }
}