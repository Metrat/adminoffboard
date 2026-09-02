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

use OCA\AdminOffboard\Service\RemoteWipeService;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Remote wipe workflow
 */
class RemoteWipeWorkflow implements WorkflowInterface
{
    private const VERSION = '1.0.0';

    public function __construct(
        private RemoteWipeService $service,
        private AppLogger $logger
    ) {
    }

    public function getName(): string
    {
        return 'remote_wipe';
    }

    public function getDescription(): string
    {
        return 'Perform remote wipe on user devices';
    }

    public function getSteps(): array
    {
        return [
            'validate_user' => 'Validate user exists',
            'get_devices' => 'Get user devices',
            'check_support' => 'Check if devices support remote wipe',
            'perform_wipe' => 'Execute remote wipe on supported devices',
            'log_audit' => 'Log audit trail'
        ];
    }

    public function execute(array $context): array
    {
        $this->logger->info('Executing remote wipe workflow', $context);

        $userId = $context['user_id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }

        $deviceId = $context['device_id'] ?? null;
        $dryRun = $context['dry_run'] ?? false;
        $queue = $context['queue'] ?? false;
        $actor = $context['actor'] ?? 'system';

        return $this->service->wipeUser($userId, $deviceId, $dryRun, $queue, $actor);
    }

    public function validateContext(array $context): bool
    {
        if (!isset($context['user_id']) || !is_string($context['user_id'])) {
            return false;
        }
        return !empty($context['user_id']);
    }

    public function getRequiredParams(): array
    {
        return ['user_id'];
    }

    public function getOptionalParams(): array
    {
        return [
            'device_id' => null,
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