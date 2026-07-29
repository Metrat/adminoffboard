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

namespace OCA\AdminOffboard\Db\Repository;

use OCA\AdminOffboard\Db\Entity\Job;
use OCA\AdminOffboard\Db\Mapper\JobMapper;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Repository for Job entities
 */
class JobRepository
{
    public function __construct(
        private JobMapper $mapper
    ) {
    }

    /**
     * Create a new job
     */
    public function create(Job $job): Job
    {
        return $this->mapper->insert($job);
    }

    /**
     * Update an existing job
     */
    public function update(Job $job): Job
    {
        return $this->mapper->update($job);
    }

    /**
     * Find a job by ID
     *
     * @throws DoesNotExistException
     */
    public function find(int $id): Job
    {
        return $this->mapper->find($id);
    }

    /**
     * Find pending jobs
     */
    public function findPending(int $limit = 100): array
    {
        return $this->mapper->findPending($limit);
    }

    /**
     * Find jobs by status
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        return $this->mapper->findByStatus($status, $limit, $offset);
    }

    /**
     * Find jobs by type
     */
    public function findByType(string $type, int $limit = 100): array
    {
        return $this->mapper->findByType($type, $limit);
    }

    /**
     * Find jobs by user
     */
    public function findByUser(string $userId, int $limit = 100): array
    {
        return $this->mapper->findByUser($userId, $limit);
    }

    /**
     * Find jobs created by user
     */
    public function findByCreator(string $userId, int $limit = 100): array
    {
        return $this->mapper->findByCreator($userId, $limit);
    }

    /**
     * Find stale jobs (stuck in processing)
     */
    public function findStaleJobs(int $timeoutSeconds = 300): array
    {
        return $this->mapper->findStaleJobs($timeoutSeconds);
    }

    /**
     * Count jobs by status
     */
    public function countByStatus(string $status): int
    {
        return $this->mapper->countByStatus($status);
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats(): array
    {
        return [
            'pending' => $this->countByStatus(Job::STATUS_PENDING),
            'processing' => $this->countByStatus(Job::STATUS_PROCESSING),
            'completed' => $this->countByStatus(Job::STATUS_COMPLETED),
            'failed' => $this->countByStatus(Job::STATUS_FAILED),
            'cancelled' => $this->countByStatus(Job::STATUS_CANCELLED),
        ];
    }

    /**
     * Delete a job
     */
    public function delete(Job $job): void
    {
        $this->mapper->delete($job);
    }

    /**
     * Delete old completed jobs
     */
    public function deleteOldCompleted(int $days): int
    {
        return $this->mapper->deleteOldCompleted($days);
    }

    /**
     * Get jobs with offset
     */
    public function getJobs(int $limit = 100, int $offset = 0): array
    {
        return $this->mapper->getJobs($limit, $offset);
    }
}