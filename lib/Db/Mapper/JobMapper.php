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

namespace OCA\AdminOffboard\Db\Mapper;

use OCA\AdminOffboard\Db\Entity\Job;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Connection;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Database mapper for Job entities
 */
class JobMapper extends QBMapper
{
    public const TABLE_NAME = 'adminoffboard_jobs';

    public function __construct(
        IDBConnection $db,
        private Connection $connection
    ) {
        parent::__construct($db, self::TABLE_NAME, Job::class);
    }

    /**
     * Find a job by ID
     */
    public function find(int $id): Job
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /**
     * Find pending jobs
     */
    public function findPending(int $limit = 100): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('status', $qb->createNamedParameter(Job::STATUS_PENDING)))
            ->orderBy('priority', 'DESC')
            ->addOrderBy('created_at', 'ASC')
            ->setMaxResults($limit);

        return $this->findEntities($qb);
    }

    /**
     * Find jobs by status
     */
    public function findByStatus(string $status, int $limit = 100, int $offset = 0): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('status', $qb->createNamedParameter($status)))
            ->orderBy('created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    /**
     * Find jobs by type
     */
    public function findByType(string $type, int $limit = 100): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('job_type', $qb->createNamedParameter($type)))
            ->orderBy('created_at', 'DESC')
            ->setMaxResults($limit);

        return $this->findEntities($qb);
    }

    /**
     * Find jobs by user
     */
    public function findByUser(string $userId, int $limit = 100): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('created_at', 'DESC')
            ->setMaxResults($limit);

        return $this->findEntities($qb);
    }

    /**
     * Find jobs by creator
     */
    public function findByCreator(string $userId, int $limit = 100): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('created_by', $qb->createNamedParameter($userId)))
            ->orderBy('created_at', 'DESC')
            ->setMaxResults($limit);

        return $this->findEntities($qb);
    }

    /**
     * Find stale jobs (stuck in processing)
     */
    public function findStaleJobs(int $timeoutSeconds = 300): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('status', $qb->createNamedParameter(Job::STATUS_PROCESSING)))
            ->andWhere($qb->expr()->lt('started_at', $qb->createNamedParameter(time() - $timeoutSeconds)));

        return $this->findEntities($qb);
    }

    /**
     * Count jobs by status
     */
    public function countByStatus(string $status): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('status', $qb->createNamedParameter($status)));

        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Delete old completed jobs
     */
    public function deleteOldCompleted(int $days): int
    {
        $cutoff = time() - ($days * 24 * 60 * 60);

        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->eq('status', $qb->createNamedParameter(Job::STATUS_COMPLETED)))
            ->andWhere($qb->expr()->lt('completed_at', $qb->createNamedParameter($cutoff)));

        return $qb->execute();
    }

    /**
     * Get jobs with offset
     */
    public function getJobs(int $limit = 100, int $offset = 0): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->orderBy('created_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }
}