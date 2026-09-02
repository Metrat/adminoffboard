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

use OCA\AdminOffboard\Db\Entity\Job;
use OCA\AdminOffboard\Db\Repository\JobRepository;
use OCA\AdminOffboard\Logger\AppLogger;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Queue management service
 */
class JobQueue
{
    public function __construct(
        private JobRepository $repository,
        private AppLogger $logger
    ) {
    }

    /**
     * Create a new job in the queue
     */
    public function createJob(
        string $jobType,
        array $payload,
        ?string $userId = null,
        ?string $createdBy = null,
        int $priority = 5
    ): Job {
        $job = new Job();
        $job->setJobType($jobType);
        $job->setStatus(Job::STATUS_PENDING);
        $job->setPayload($payload);
        $job->setUserId($userId);
        $job->setCreatedBy($createdBy);
        $job->setCreatedAt(time());
        $job->setAttempts(0);
        $job->setMaxAttempts(3);
        $job->setPriority($priority);

        $this->logger->debug('Creating new job', [
            'type' => $jobType,
            'userId' => $userId,
            'createdBy' => $createdBy
        ]);

        return $this->repository->create($job);
    }

    /**
     * Get the next pending job
     */
    public function getNextJob(): ?Job
    {
        $jobs = $this->repository->findPending(1);
        return !empty($jobs) ? $jobs[0] : null;
    }

    /**
     * Mark a job as processing
     */
    public function startProcessing(Job $job): void
    {
        $job->setStatus(Job::STATUS_PROCESSING);
        $job->setStartedAt(time());
        $this->repository->update($job);
        
        $this->logger->debug('Started processing job', [
            'jobId' => $job->getId(),
            'type' => $job->getJobType()
        ]);
    }

    /**
     * Mark a job as completed
     */
    public function completeJob(Job $job): void
    {
        $job->setStatus(Job::STATUS_COMPLETED);
        $job->setCompletedAt(time());
        $this->repository->update($job);
        
        $this->logger->debug('Completed job', [
            'jobId' => $job->getId(),
            'duration' => $job->getDuration()
        ]);
    }

    /**
     * Mark a job as failed
     */
    public function failJob(Job $job, string $errorMessage): void
    {
        $job->incrementAttempts();
        
        if ($job->canRetry()) {
            // Reset to pending for retry
            $job->setStatus(Job::STATUS_PENDING);
            $job->setStartedAt(null);
            $job->setErrorMessage($errorMessage);
            
            $this->logger->warning('Job failed, will retry', [
                'jobId' => $job->getId(),
                'attempt' => $job->getAttempts(),
                'maxAttempts' => $job->getMaxAttempts(),
                'error' => $errorMessage
            ]);
        } else {
            // Mark as permanently failed
            $job->setStatus(Job::STATUS_FAILED);
            $job->setErrorMessage($errorMessage);
            $job->setCompletedAt(time());
            
            $this->logger->error('Job permanently failed', [
                'jobId' => $job->getId(),
                'attempts' => $job->getAttempts(),
                'error' => $errorMessage
            ]);
        }
        
        $this->repository->update($job);
    }

    /**
     * Cancel a job
     */
    public function cancelJob(Job $job): void
    {
        if ($job->isPending()) {
            $job->setStatus(Job::STATUS_CANCELLED);
            $this->repository->update($job);
            
            $this->logger->info('Job cancelled', [
                'jobId' => $job->getId()
            ]);
        } else {
            throw new \RuntimeException('Cannot cancel a job that is not pending');
        }
    }

    /**
     * Get queue statistics
     */
    public function getStats(): array
    {
        return $this->repository->getQueueStats();
    }

    /**
     * Process stale jobs (jobs stuck in processing)
     */
    public function recoverStaleJobs(int $timeoutSeconds = 300): int
    {
        $staleJobs = $this->repository->findStaleJobs($timeoutSeconds);
        $recovered = 0;

        foreach ($staleJobs as $job) {
            $job->setStatus(Job::STATUS_PENDING);
            $job->setStartedAt(null);
            $job->setErrorMessage('Recovered from stale state');
            $this->repository->update($job);
            $recovered++;
            
            $this->logger->warning('Recovered stale job', [
                'jobId' => $job->getId()
            ]);
        }

        return $recovered;
    }

    /**
     * Get job by ID
     */
    public function getJob(int $id): ?Job
    {
        try {
            return $this->repository->find($id);
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * Get jobs with pagination
     */
    public function getJobs(int $limit = 100, int $offset = 0): array
    {
        return $this->repository->getJobs($limit, $offset);
    }

    /**
     * Clean up old jobs
     */
    public function cleanupOldJobs(int $days): int
    {
        return $this->repository->deleteOldCompleted($days);
    }
}