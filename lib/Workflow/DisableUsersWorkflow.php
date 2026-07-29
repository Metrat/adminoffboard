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

namespace OCA\AdminOffboard\Workflow;

use OCA\AdminOffboard\Service\UserManagementService;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Disable users workflow
 */
class DisableUsersWorkflow implements WorkflowInterface
{
    private const VERSION = '1.0.0';

    public function __construct(
        private UserManagementService $service,
        private AppLogger $logger
    ) {
    }

    public function getName(): string
    {
        return 'disable_users';
    }

    public function getDescription(): string
    {
        return 'Disable multiple user accounts in bulk';
    }

    public function getSteps(): array
    {
        return [
            'validate_users' => 'Validate all users exist and are not self',
            'disable_users' => 'Disable each user account',
            'log_audit' => 'Log audit trail for each user'
        ];
    }

    public function execute(array $context): array
    {
        $this->logger->info('Executing disable users workflow', $context);

        $userIds = $context['user_ids'] ?? null;
        if (empty($userIds)) {
            throw new \InvalidArgumentException('User IDs are required');
        }

        if (!is_array($userIds)) {
            throw new \InvalidArgumentException('User IDs must be an array');
        }

        $dryRun = $context['dry_run'] ?? false;
        $queue = $context['queue'] ?? false;
        $actor = $context['actor'] ?? 'system';

        return $this->service->disableUsers($userIds, $dryRun, $queue, $actor);
    }

    public function validateContext(array $context): bool
    {
        if (!isset($context['user_ids']) || !is_array($context['user_ids'])) {
            return false;
        }
        return !empty($context['user_ids']);
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

    public function supportsDryRun(): bool
    {
        return true;
    }

    public function supportsQueue(): bool
    {
        return true;
    }

    public function getVersion(): string
    {
        return self::VERSION;
    }
}