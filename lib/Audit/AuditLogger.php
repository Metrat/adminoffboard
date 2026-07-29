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

namespace OCA\AdminOffboard\Audit;

use OCA\AdminOffboard\Db\Entity\AuditLog;
use OCA\AdminOffboard\Db\Repository\AuditLogRepository;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Audit logger service
 */
class AuditLogger
{
    public const ACTION_OFFBOARD = 'offboard';
    public const ACTION_DISABLE_USERS = 'disable_users';
    public const ACTION_DELETE_TOKENS = 'delete_tokens';
    public const ACTION_REMOTE_WIPE = 'remote_wipe';
    public const ACTION_QUEUE_PROCESS = 'queue_process';
    public const ACTION_CONFIG_CHANGE = 'config_change';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILURE = 'failure';
    public const STATUS_PARTIAL = 'partial';

    public function __construct(
        private AuditLogRepository $repository,
        private AppLogger $logger
    ) {
    }

    /**
     * Log an audit entry
     */
    public function log(
        string $action,
        string $userId,
        string $actor,
        ?array $details = null,
        string $status = self::STATUS_SUCCESS,
        ?string $target = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): AuditLog {
        $auditLog = new AuditLog();
        $auditLog->setAction($action);
        $auditLog->setUserId($userId);
        $auditLog->setActor($actor);
        $auditLog->setTarget($target);
        $auditLog->setDetails($details);
        $auditLog->setStatus($status);
        $auditLog->setIpAddress($ipAddress ?? $this->getClientIp());
        $auditLog->setUserAgent($userAgent ?? $this->getUserAgent());
        $auditLog->setTimestamp(time());

        $this->repository->create($auditLog);

        $this->logger->info('Audit log created', [
            'action' => $action,
            'userId' => $userId,
            'actor' => $actor,
            'status' => $status
        ]);

        return $auditLog;
    }

    /**
     * Log a successful operation
     */
    public function logSuccess(
        string $action,
        string $userId,
        string $actor,
        ?array $details = null,
        ?string $target = null
    ): AuditLog {
        return $this->log($action, $userId, $actor, $details, self::STATUS_SUCCESS, $target);
    }

    /**
     * Log a failed operation
     */
    public function logFailure(
        string $action,
        string $userId,
        string $actor,
        ?array $details = null,
        ?string $target = null
    ): AuditLog {
        return $this->log($action, $userId, $actor, $details, self::STATUS_FAILURE, $target);
    }

    /**
     * Log a partial success
     */
    public function logPartial(
        string $action,
        string $userId,
        string $actor,
        ?array $details = null,
        ?string $target = null
    ): AuditLog {
        return $this->log($action, $userId, $actor, $details, self::STATUS_PARTIAL, $target);
    }

    /**
     * Get audit logs for a user
     */
    public function getForUser(string $userId, int $limit = 100, int $offset = 0): array
    {
        return $this->repository->findByUser($userId, $limit, $offset);
    }

    /**
     * Get audit logs by action
     */
    public function getByAction(string $action, int $limit = 100, int $offset = 0): array
    {
        return $this->repository->findByAction($action, $limit, $offset);
    }

    /**
     * Get recent audit logs
     */
    public function getRecent(int $limit = 100): array
    {
        return $this->repository->findRecent($limit);
    }

    /**
     * Get audit logs in date range
     */
    public function getByDateRange(int $from, int $to, int $limit = 100): array
    {
        return $this->repository->findByDateRange($from, $to, $limit);
    }

    /**
     * Clean up old audit logs
     */
    public function cleanupOldLogs(int $days): int
    {
        return $this->repository->deleteOldLogs($days);
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): ?string
    {
        // Check for proxy headers first
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get user agent
     */
    private function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }
}