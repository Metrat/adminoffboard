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
 * Service for token management operations
 */
class TokenManagementService
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
     * Delete tokens for multiple users
     */
    public function deleteTokens(
        array $userIds,
        bool $dryRun = false,
        bool $queue = false,
        string $actor = 'system'
    ): array {
        $this->logger->info('Deleting tokens for multiple users', [
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
            throw new ValidationException('No valid users to delete tokens');
        }

        // Queue if requested
        if ($queue) {
            $job = $this->queueManager->createDeleteTokensJob($validUsers, $actor);
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
                    AuditLogger::ACTION_DELETE_TOKENS,
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
        $totalTokensDeleted = 0;

        foreach ($validUsers as $userId) {
            try {
                $deleted = $this->adapter->deleteAllTokens($userId);
                $results[$userId] = $deleted;
                
                if ($deleted) {
                    $successCount++;
                    $totalTokensDeleted++;
                    $this->auditLogger->log(
                        AuditLogger::ACTION_DELETE_TOKENS,
                        $userId,
                        $actor,
                        ['tokens_deleted' => true],
                        AuditLogger::STATUS_SUCCESS
                    );
                } else {
                    $failCount++;
                    $this->auditLogger->log(
                        AuditLogger::ACTION_DELETE_TOKENS,
                        $userId,
                        $actor,
                        ['error' => 'Failed to delete tokens'],
                        AuditLogger::STATUS_FAILURE
                    );
                }
            } catch (\Exception $e) {
                $failCount++;
                $results[$userId] = false;
                $this->auditLogger->log(
                    AuditLogger::ACTION_DELETE_TOKENS,
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
            'tokens_deleted' => $totalTokensDeleted,
            'results' => $results
        ];
    }

    /**
     * Delete tokens for a specific user
     */
    public function deleteUserTokens(
        string $userId,
        bool $dryRun = false,
        string $actor = 'system'
    ): array {
        $this->logger->info('Deleting tokens for user', [
            'user_id' => $userId,
            'dry_run' => $dryRun,
            'actor' => $actor
        ]);

        $this->userValidator->validateUserExists($userId);

        if ($dryRun) {
            $this->auditLogger->log(
                AuditLogger::ACTION_DELETE_TOKENS,
                $userId,
                $actor,
                ['dry_run' => true],
                AuditLogger::STATUS_SUCCESS
            );
            return [
                'status' => 'dry_run',
                'user_id' => $userId
            ];
        }

        $deleted = $this->adapter->deleteAllTokens($userId);

        $this->auditLogger->log(
            AuditLogger::ACTION_DELETE_TOKENS,
            $userId,
            $actor,
            ['tokens_deleted' => $deleted],
            $deleted ? AuditLogger::STATUS_SUCCESS : AuditLogger::STATUS_FAILURE
        );

        return [
            'status' => $deleted ? 'success' : 'failed',
            'user_id' => $userId,
            'tokens_deleted' => $deleted
        ];
    }
}