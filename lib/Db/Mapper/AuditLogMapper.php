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

use OCA\AdminOffboard\Db\Entity\AuditLog;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Database mapper for AuditLog entities
 */
class AuditLogMapper extends QBMapper
{
    public const TABLE_NAME = 'adminoffboard_audit';

    public function __construct(
        IDBConnection $db
    ) {
        parent::__construct($db, self::TABLE_NAME, AuditLog::class);
    }

    /**
     * Find audit log by ID
     */
    public function find(int $id): AuditLog
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    /**
     * Find audit logs by user
     */
    public function findByUser(string $userId, int $limit = 100, int $offset = 0): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('timestamp', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    /**
     * Find audit logs by actor
     */
    public function findByActor(string $actor, int $limit = 100, int $offset = 0): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('actor', $qb->createNamedParameter($actor)))
            ->orderBy('timestamp', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    /**
     * Find audit logs by action
     */
    public function findByAction(string $action, int $limit = 100, int $offset = 0): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('action', $qb->createNamedParameter($action)))
            ->orderBy('timestamp', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    /**
     * Find recent audit logs
     */
    public function findRecent(int $limit = 100): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->orderBy('timestamp', 'DESC')
            ->setMaxResults($limit);

        return $this->findEntities($qb);
    }

    /**
     * Find audit logs by date range
     */
    public function findByDateRange(int $from, int $to, int $limit = 100): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->gte('timestamp', $qb->createNamedParameter($from)))
            ->andWhere($qb->expr()->lte('timestamp', $qb->createNamedParameter($to)))
            ->orderBy('timestamp', 'DESC')
            ->setMaxResults($limit);

        return $this->findEntities($qb);
    }

    /**
     * Count audit logs by action
     */
    public function countByAction(string $action): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('action', $qb->createNamedParameter($action)));

        $result = $qb->executeStatement();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Count audit logs by user
     */
    public function countByUser(string $userId): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeStatement();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Count total audit logs
     */
    public function countAll(): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName());

        $result = $qb->executeStatement();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Count recent audit logs
     */
    public function countRecent(int $seconds): int
    {
        $cutoff = time() - $seconds;
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->expr()->count('id', 'count'))
            ->from($this->getTableName())
            ->where($qb->expr()->gte('timestamp', $qb->createNamedParameter($cutoff)));

        $result = $qb->executeStatement();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? (int)$row['count'] : 0;
    }

    /**
     * Delete old audit logs
     */
    public function deleteOldLogs(int $days): int
    {
        $cutoff = time() - ($days * 24 * 60 * 60);

        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
            ->where($qb->expr()->lt('timestamp', $qb->createNamedParameter($cutoff)));

        return $qb->executeStatement();
    }

    /**
     * Get audit logs with pagination
     */
    public function getLogs(int $limit = 100, int $offset = 0): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->orderBy('timestamp', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    /**
     * Search audit logs
     */
    public function search(string $search, int $limit = 100): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->like('user_id', $qb->createNamedParameter('%' . $search . '%')))
            ->orWhere($qb->expr()->like('actor', $qb->createNamedParameter('%' . $search . '%')))
            ->orWhere($qb->expr()->like('action', $qb->createNamedParameter('%' . $search . '%')))
            ->orWhere($qb->expr()->like('target', $qb->createNamedParameter('%' . $search . '%')))
            ->orderBy('timestamp', 'DESC')
            ->setMaxResults($limit);

        return $this->findEntities($qb);
    }
}