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

use OCA\AdminOffboard\Audit\AuditLogger;
use OCA\AdminOffboard\Audit\AuditDataCollector;
use OCA\AdminOffboard\Db\Repository\AuditLogRepository;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Service for audit operations
 */
class AuditService
{
    public function __construct(
        private AuditLogRepository $repository,
        private AuditLogger $auditLogger,
        private AuditDataCollector $dataCollector,
        private AppLogger $logger
    ) {
    }

    /**
     * Get audit logs with filters
     */
    public function getLogs(
        ?string $userId = null,
        ?string $actor = null,
        ?string $action = null,
        ?int $from = null,
        ?int $to = null,
        int $limit = 100,
        int $offset = 0
    ): array {
        $this->logger->debug('Getting audit logs', [
            'user_id' => $userId,
            'actor' => $actor,
            'action' => $action,
            'limit' => $limit,
            'offset' => $offset
        ]);

        if ($userId) {
            return $this->repository->findByUser($userId, $limit, $offset);
        }

        if ($actor) {
            return $this->repository->findByActor($actor, $limit, $offset);
        }

        if ($action) {
            return $this->repository->findByAction($action, $limit, $offset);
        }

        if ($from && $to) {
            return $this->repository->findByDateRange($from, $to, $limit);
        }

        return $this->repository->findRecent($limit);
    }

    /**
     * Get audit statistics
     */
    public function getStatistics(): array
    {
        return $this->dataCollector->getStatistics();
    }

    /**
     * Get user activity report
     */
    public function getUserActivityReport(string $userId, int $days = 30): array
    {
        return $this->dataCollector->getUserActivityReport($userId, $days);
    }

    /**
     * Get admin activity report
     */
    public function getAdminActivityReport(string $actor, int $days = 30): array
    {
        return $this->dataCollector->getAdminActivityReport($actor, $days);
    }

    /**
     * Get action summary report
     */
    public function getActionSummaryReport(int $days = 30): array
    {
        return $this->dataCollector->getActionSummaryReport($days);
    }

    /**
     * Search audit logs
     */
    public function searchLogs(string $search, int $limit = 100): array
    {
        return $this->repository->search($search, $limit);
    }

    /**
     * Clean up old logs
     */
    public function cleanupLogs(int $days): int
    {
        $this->logger->info('Cleaning up audit logs', ['days' => $days]);
        $deleted = $this->repository->deleteOldLogs($days);

        $this->auditLogger->log(
            AuditLogger::ACTION_CONFIG_CHANGE,
            'system',
            'system',
            [
                'action' => 'cleanup_audit_logs',
                'days' => $days,
                'deleted' => $deleted
            ],
            AuditLogger::STATUS_SUCCESS
        );

        return $deleted;
    }

    /**
     * Get log count
     */
    public function getLogCount(?string $action = null, ?string $userId = null): int
    {
        if ($action) {
            return $this->repository->countByAction($action);
        }

        if ($userId) {
            return $this->repository->countByUser($userId);
        }

        return $this->repository->countAll();
    }
}