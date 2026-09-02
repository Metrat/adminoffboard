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

namespace OCA\AdminOffboard\Operation;

use OCA\AdminOffboard\Adapter\NextcloudAdapter;
use OCA\AdminOffboard\Queue\QueueManager;
use OCA\AdminOffboard\Exception\OperationFailedException;
use OCA\AdminOffboard\Audit\AuditLogger;

/**
 * Disable users operation
 */
class DisableUsersOperation extends BaseOperation
{
    public function __construct(
        private NextcloudAdapter $adapter,
        private QueueManager $queueManager,
        AppLogger $logger,
        AuditLogger $auditLogger
    ) {
        parent::__construct($logger, $auditLogger);
    }

    public function getName(): string
    {
        return 'disable_users';
    }

    public function getDescription(): string
    {
        return 'Disable multiple user accounts in bulk';
    }

    public function getRequiredParams(): array
    {
        return ['user_ids'];
    }

    public function getOptionalParams(): array
    {
        return [
            'dry_run' => false,
            'queue' => false,
            'actor' => 'system'
        ];
    }

    public function validateContext(array $context): bool
    {
        if (!isset($context['user_ids']) || !is_array($context['user_ids'])) {
            return false;
        }
        return !empty($context['user_ids']);
    }

    public function estimateImpact(array $context): int
    {
        return count($context['user_ids'] ?? []);
    }

    public function execute(array $context): array
    {
        $this->logStart($this->getName(), $context);

        $userIds = $context['user_ids'];
        $dryRun = $context['dry_run'] ?? false;
        $queue = $context['queue'] ?? false;
        $actor = $context['actor'] ?? 'system';

        try {
            if ($queue) {
                $job = $this->queueManager->createDisableUsersJob($userIds, $actor);
                $result = [
                    'status' => 'queued',
                    'job_id' => $job->getId(),
                    'total' => count($userIds)
                ];
                $this->logComplete($this->getName(), $result);
                return $result;
            }

            if ($dryRun) {
                foreach ($userIds as $userId) {
                    $this->auditLogger->log(
                        AuditLogger::ACTION_DISABLE_USERS,
                        $userId,
                        $actor,
                        ['dry_run' => true],
                        AuditLogger::STATUS_SUCCESS
                    );
                }
                $result = [
                    'status' => 'dry_run',
                    'total' => count($userIds),
                    'users' => $userIds
                ];
                $this->logComplete($this->getName(), $result);
                return $result;
            }

            $results = [];
            $successCount = 0;
            $failCount = 0;

            foreach ($userIds as $userId) {
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

            $result = [
                'status' => 'completed',
                'total' => count($userIds),
                'success' => $successCount,
                'failed' => $failCount,
                'results' => $results
            ];

            $this->logComplete($this->getName(), $result);
            return $result;

        } catch (\Exception $e) {
            $this->logError($this->getName(), $e, $context);
            throw new OperationFailedException(
                "Disable users failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}