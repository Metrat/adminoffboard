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

use OCA\AdminOffboard\Service\OffboardService;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Offboard workflow
 */
class OffboardWorkflow implements WorkflowInterface
{
    private const VERSION = '1.0.0';

    public function __construct(
        private OffboardService $service,
        private AppLogger $logger
    ) {
    }

    public function getName(): string
    {
        return 'offboard';
    }

    public function getDescription(): string
    {
        return 'Offboard a user by disabling account, deleting tokens, and optional remote wipe';
    }

    public function getSteps(): array
    {
        return [
            'validate_user' => 'Validate user exists and is not self',
            'disable_user' => 'Disable user account',
            'delete_tokens' => 'Delete all device tokens',
            'remote_wipe' => 'Remote wipe all devices (optional)',
            'log_audit' => 'Log audit trail'
        ];
    }

    public function execute(array $context): array
    {
        $this->logger->info('Executing offboard workflow', $context);

        $userId = $context['user_id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }

        $remoteWipe = $context['remote_wipe'] ?? false;
        $dryRun = $context['dry_run'] ?? false;
        $queue = $context['queue'] ?? false;
        $actor = $context['actor'] ?? 'system';

        return $this->service->offboardUser($userId, $remoteWipe, $dryRun, $queue, $actor);
    }

    public function validateContext(array $context): bool
    {
        return isset($context['user_id']) && is_string($context['user_id']);
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