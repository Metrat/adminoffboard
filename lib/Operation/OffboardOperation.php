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
 * Offboard operation
 */
class OffboardOperation extends BaseOperation
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
        return 'offboard';
    }

    public function getDescription(): string
    {
        return 'Offboard a user by disabling account, deleting tokens, and optional remote wipe';
    }

    public function getRequiredParams(): array
    {
        return ['user_id'];
    }

    public function getOptionalParams(): array
    {
        return [
            'remote_wipe' => false,
            'dry_run' => false,
            'queue' => false,
            'actor' => 'system'
        ];
    }

    public function validateContext(array $context): bool
    {
        if (!isset($context['user_id']) || !is_string($context['user_id'])) {
            return false;
        }
        return !empty($context['user_id']);
    }

    public function estimateImpact(array $context): int
    {
        return 1;
    }

    public function execute(array $context): array
    {
        $this->logStart($this->getName(), $context);

        $userId = $context['user_id'];
        $remoteWipe = $context['remote_wipe'] ?? false;
        $dryRun = $context['dry_run'] ?? false;
        $queue = $context['queue'] ?? false;
        $actor = $context['actor'] ?? 'system';

        try {
            if ($queue) {
                $job = $this->queueManager->createOffboardJob($userId, $remoteWipe, $actor);
                $result = [
                    'status' => 'queued',
                    'job_id' => $job->getId(),
                    'user_id' => $userId
                ];
                $this->logComplete($this->getName(), $result);
                return $result;
            }

            if ($dryRun) {
                $this->auditLogger->log(
                    AuditLogger::ACTION_OFFBOARD,
                    $userId,
                    $actor,
                    ['dry_run' => true, 'remote_wipe' => $remoteWipe],
                    AuditLogger::STATUS_SUCCESS
                );
                $result = [
                    'status' => 'dry_run',
                    'user_id' => $userId,
                    'remote_wipe' => $remoteWipe
                ];
                $this->logComplete($this->getName(), $result);
                return $result;
            }

            $results = [];

            $disabled = $this->adapter->disableUser($userId);
            $results['disabled'] = $disabled;
            if (!$disabled) {
                throw new OperationFailedException("Failed to disable user: $userId");
            }

            $tokensDeleted = $this->adapter->deleteAllTokens($userId);
            $results['tokens_deleted'] = $tokensDeleted;
            if (!$tokensDeleted) {
                throw new OperationFailedException("Failed to delete tokens for user: $userId");
            }

            if ($remoteWipe) {
                $wipeResult = $this->adapter->remoteWipeUser($userId);
                $results['remote_wipe'] = $wipeResult;
                if (!$wipeResult) {
                    throw new OperationFailedException("Remote wipe failed for user: $userId");
                }
            }

            $this->auditLogger->log(
                AuditLogger::ACTION_OFFBOARD,
                $userId,
                $actor,
                $results,
                AuditLogger::STATUS_SUCCESS
            );

            $result = [
                'status' => 'success',
                'user_id' => $userId,
                'results' => $results
            ];

            $this->logComplete($this->getName(), $result);
            return $result;

        } catch (\Exception $e) {
            $this->logError($this->getName(), $e, $context);
            
            $this->auditLogger->log(
                AuditLogger::ACTION_OFFBOARD,
                $userId,
                $actor,
                ['error' => $e->getMessage()],
                AuditLogger::STATUS_FAILURE
            );

            throw new OperationFailedException(
                "Offboard failed for user $userId: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}