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

use OCA\AdminOffboard\Configuration\AppConfig;
use OCA\AdminOffboard\Db\Entity\Job;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Queue manager - coordinates queue operations
 */
class QueueManager
{
    private bool $isProcessing = false;

    public function __construct(
        private JobQueue $queue,
        private JobProcessor $processor,
        private AppConfig $config,
        private AppLogger $logger
    ) {
    }

    /**
     * Process the next job in the queue
     */
    public function processNextJob(): bool
    {
        if ($this->isProcessing) {
            $this->logger->warning('Queue is already processing a job');
            return false;
        }

        $job = $this->queue->getNextJob();
        if (!$job) {
            return false;
        }

        $this->isProcessing = true;
        
        try {
            $this->queue->startProcessing($job);
            
            $success = $this->processor->processJob($job);
            
            if ($success) {
                $this->queue->completeJob($job);
                $this->logger->info('Job processed successfully', [
                    'jobId' => $job->getId(),
                    'type' => $job->getJobType()
                ]);
            } else {
                $this->queue->failJob($job, 'Job processing returned false');
                $this->logger->warning('Job processing returned false', [
                    'jobId' => $job->getId()
                ]);
            }
            
            $this->isProcessing = false;
            return true;
            
        } catch (\Exception $e) {
            $this->queue->failJob($job, $e->getMessage());
            $this->logger->error('Job processing failed with exception', [
                'jobId' => $job->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->isProcessing = false;
            return false;
        }
    }

    /**
     * Process multiple jobs from the queue
     */
    public function processJobs(int $count = 1): int
    {
        $processed = 0;
        $batchSize = min($count, $this->config->getQueueBatchSize());

        for ($i = 0; $i < $batchSize; $i++) {
            if ($this->processNextJob()) {
                $processed++;
            } else {
                break;
            }
        }

        if ($processed > 0) {
            $this->logger->info('Processed batch of jobs', [
                'processed' => $processed,
                'batch_size' => $batchSize
            ]);
        }

        return $processed;
    }

    /**
     * Process jobs until queue is empty or limit reached
     */
    public function processAll(int $maxJobs = 0): int
    {
        $processed = 0;
        $limit = $maxJobs > 0 ? $maxJobs : PHP_INT_MAX;

        while ($processed < $limit) {
            if (!$this->processNextJob()) {
                break;
            }
            $processed++;
        }

        $this->logger->info('Processed all available jobs', [
            'processed' => $processed,
            'limit' => $limit
        ]);

        return $processed;
    }

    /**
     * Get queue statistics
     */
    public function getStats(): array
    {
        return $this->queue->getStats();
    }

    /**
     * Recover stale jobs
     */
    public function recoverStaleJobs(): int
    {
        return $this->queue->recoverStaleJobs();
    }

    /**
     * Clean up old completed jobs
     */
    public function cleanupOldJobs(): int
    {
        $retentionDays = $this->config->getAuditLogRetentionDays();
        return $this->queue->cleanupOldJobs($retentionDays);
    }

    /**
     * Create a new offboard job
     */
    public function createOffboardJob(
        string $userId,
        bool $remoteWipe = false,
        ?string $createdBy = null,
        int $priority = Job::PRIORITY_NORMAL
    ): Job {
        $payload = [
            'user_id' => $userId,
            'remote_wipe' => $remoteWipe
        ];

        return $this->queue->createJob(
            Job::TYPE_OFFBOARD,
            $payload,
            $userId,
            $createdBy,
            $priority
        );
    }

    /**
     * Create a new disable users job
     */
    public function createDisableUsersJob(
        array $userIds,
        ?string $createdBy = null,
        int $priority = Job::PRIORITY_NORMAL
    ): Job {
        $payload = [
            'user_ids' => $userIds
        ];

        return $this->queue->createJob(
            Job::TYPE_DISABLE_USERS,
            $payload,
            null,
            $createdBy,
            $priority
        );
    }

    /**
     * Create a new delete tokens job
     */
    public function createDeleteTokensJob(
        array $userIds,
        ?string $createdBy = null,
        int $priority = Job::PRIORITY_NORMAL
    ): Job {
        $payload = [
            'user_ids' => $userIds
        ];

        return $this->queue->createJob(
            Job::TYPE_DELETE_TOKENS,
            $payload,
            null,
            $createdBy,
            $priority
        );
    }

    /**
     * Create a new remote wipe job
     */
    public function createRemoteWipeJob(
        string $userId,
        ?string $deviceId = null,
        ?string $createdBy = null,
        int $priority = Job::PRIORITY_HIGH
    ): Job {
        $payload = [
            'user_id' => $userId,
            'device_id' => $deviceId
        ];

        return $this->queue->createJob(
            Job::TYPE_REMOTE_WIPE,
            $payload,
            $userId,
            $createdBy,
            $priority
        );
    }

    /**
     * Get job by ID
     */
    public function getJob(int $id): ?Job
    {
        return $this->queue->getJob($id);
    }

    /**
     * Cancel a job
     */
    public function cancelJob(int $id): bool
    {
        $job = $this->queue->getJob($id);
        if (!$job) {
            return false;
        }

        try {
            $this->queue->cancelJob($job);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel job', [
                'jobId' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}