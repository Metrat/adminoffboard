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
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Service for user management operations
 */
class UserManagementService
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
     * Disable multiple users
     */
    public function disableUsers(
        array $userIds,
        bool $dryRun = false,
        bool $queue = false,
        string $actor = 'system'
    ): array {
        $this->logger->info('Disabling multiple users', [
            'user_count' => count($userIds),
            'dry_run' => $dryRun,
            'queue' => $queue,
            'actor' => $actor
        ]);

        // Validate users
        $validUsers = [];
        $invalidUsers = [];
        foreach ($userIds as $userId) {
            try {
                $this->userValidator->validateUserExists($userId);
                $this->userValidator->validateNotSelf($userId, $actor);
                $validUsers[] = $userId;
            } catch (\Exception $e) {
                $invalidUsers[] = $userId;
                $this->logger->warning('Invalid user skipped', [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (empty($validUsers)) {
            throw new ValidationException('No valid users to disable');
        }

        // Queue if requested
        if ($queue) {
            $job = $this->queueManager->createDisableUsersJob($validUsers, $actor);
            return [
                'status' => 'queued',
                'job_id' => $job->getId(),
                'total' => count($validUsers),
                'invalid' => $invalidUsers
            ];
        }

        // Dry run
        if ($dryRun) {
            foreach ($validUsers as $userId) {
                $this->auditLogger->log(
                    AuditLogger::ACTION_DISABLE_USERS,
                    $userId,
                    $actor,
                    ['dry_run' => true],
                    AuditLogger::STATUS_SUCCESS
                );
            }
            return [
                'status' => 'dry_run',
                'total' => count($validUsers),
                'users' => $validUsers
            ];
        }

        // Execute
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($validUsers as $userId) {
            try {
                $disabled = $this->adapter->disableUser($userId);
                $results[$userId] = $disabled;
                
                if ($disabled) {
                    $successCount++;
                    $this->auditLogger->log(
                        AuditLogger::ACTION_DISABLE_USERS,
                        $userId,
                        $actor,
                        [],
                        AuditLogger::STATUS_SUCCESS
                    );
                } else {
                    $failCount++;
                    $this->auditLogger->log(
                        AuditLogger::ACTION_DISABLE_USERS,
                        $userId,
                        $actor,
                        ['error' => 'Failed to disable user'],
                        AuditLogger::STATUS_FAILURE
                    );
                }
            } catch (\Exception $e) {
                $failCount++;
                $results[$userId] = false;
                $this->auditLogger->log(
                    AuditLogger::ACTION_DISABLE_USERS,
                    $userId,
                    $actor,
                    ['error' => $e->getMessage()],
                    AuditLogger::STATUS_FAILURE
                );
            }
        }

        return [
            'status' => 'completed',
            'total' => count($validUsers),
            'success' => $successCount,
            'failed' => $failCount,
            'invalid' => $invalidUsers,
            'results' => $results
        ];
    }

    /**
     * Get users with pagination
     */
    public function getUsers(
        string $search = '',
        int $limit = 50,
        int $offset = 0,
        bool $includeDisabled = true
    ): array {
        $this->logger->debug('Getting users', [
            'search' => $search,
            'limit' => $limit,
            'offset' => $offset
        ]);

        $users = $this->adapter->searchUsers($search, $limit + $offset);
        
        // Filter disabled users if needed
        if (!$includeDisabled) {
            $users = array_filter($users, function ($user) {
                return $user->isEnabled();
            });
        }

        // Apply pagination
        $users = array_slice($users, $offset, $limit);

        return [
            'users' => $users,
            'count' => count($users)
        ];
    }

    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        $allUsers = $this->adapter->getAllUsers();
        $enabled = 0;
        $disabled = 0;

        foreach ($allUsers as $user) {
            if ($user->isEnabled()) {
                $enabled++;
            } else {
                $disabled++;
            }
        }

        return [
            'total' => count($allUsers),
            'enabled' => $enabled,
            'disabled' => $disabled
        ];
    }
}