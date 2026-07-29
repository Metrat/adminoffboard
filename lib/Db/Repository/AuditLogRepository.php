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

use OCA\AdminOffboard\Db\Entity\AuditLog;
use OCA\AdminOffboard\Db\Mapper\AuditLogMapper;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Repository for AuditLog entities
 */
class AuditLogRepository
{
    public function __construct(
        private AuditLogMapper $mapper
    ) {
    }

    /**
     * Create a new audit log entry
     */
    public function create(AuditLog $auditLog): AuditLog
    {
        return $this->mapper->insert($auditLog);
    }

    /**
     * Find audit log by ID
     *
     * @throws DoesNotExistException
     */
    public function find(int $id): AuditLog
    {
        return $this->mapper->find($id);
    }

    /**
     * Find audit logs by user
     */
    public function findByUser(string $userId, int $limit = 100, int $offset = 0): array
    {
        return $this->mapper->findByUser($userId, $limit, $offset);
    }

    /**
     * Find audit logs by actor
     */
    public function findByActor(string $actor, int $limit = 100, int $offset = 0): array
    {
        return $this->mapper->findByActor($actor, $limit, $offset);
    }

    /**
     * Find audit logs by action
     */
    public function findByAction(string $action, int $limit = 100, int $offset = 0): array
    {
        return $this->mapper->findByAction($action, $limit, $offset);
    }

    /**
     * Find recent audit logs
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->mapper->findRecent($limit);
    }

    /**
     * Find audit logs by date range
     */
    public function findByDateRange(int $from, int $to, int $limit = 100): array
    {
        return $this->mapper->findByDateRange($from, $to, $limit);
    }

    /**
     * Count audit logs by action
     */
    public function countByAction(string $action): int
    {
        return $this->mapper->countByAction($action);
    }

    /**
     * Count audit logs by user
     */
    public function countByUser(string $userId): int
    {
        return $this->mapper->countByUser($userId);
    }

    /**
     * Delete old audit logs
     */
    public function deleteOldLogs(int $days): int
    {
        return $this->mapper->deleteOldLogs($days);
    }

    /**
     * Get audit logs with pagination
     */
    public function getLogs(int $limit = 100, int $offset = 0): array
    {
        return $this->mapper->getLogs($limit, $offset);
    }

    /**
     * Search audit logs
     */
    public function search(string $search, int $limit = 100): array
    {
        return $this->mapper->search($search, $limit);
    }

    /**
     * Get audit statistics
     */
    public function getStats(): array
    {
        return [
            'total' => $this->mapper->countAll(),
            'by_action' => $this->getActionStats(),
            'recent_24h' => $this->mapper->countRecent(24 * 60 * 60),
            'recent_7d' => $this->mapper->countRecent(7 * 24 * 60 * 60),
        ];
    }

    /**
     * Get action statistics
     */
    private function getActionStats(): array
    {
        $actions = [
            AuditLog::ACTION_OFFBOARD,
            AuditLog::ACTION_DISABLE_USERS,
            AuditLog::ACTION_DELETE_TOKENS,
            AuditLog::ACTION_REMOTE_WIPE,
            AuditLog::ACTION_QUEUE_PROCESS,
            AuditLog::ACTION_CONFIG_CHANGE,
        ];

        $stats = [];
        foreach ($actions as $action) {
            $stats[$action] = $this->countByAction($action);
        }

        return $stats;
    }
}