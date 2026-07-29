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

namespace OCA\AdminOffboard\Driver;

use OCA\AdminOffboard\Adapter\TokenAdapter;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Base driver class with common functionality
 */
abstract class BaseDriver implements DriverInterface
{
    protected const VERSION = '1.0.0';

    public function __construct(
        protected TokenAdapter $tokenAdapter,
        protected AppLogger $logger
    ) {
    }

    /**
     * Get driver version
     */
    public function getVersion(): string
    {
        return static::VERSION;
    }

    /**
     * Get driver capabilities
     */
    public function getCapabilities(): array
    {
        return [
            'remote_wipe' => $this->supportsRemoteWipe(),
            'device_info' => true,
            'activity_tracking' => true,
        ];
    }

    /**
     * Check if device is active
     */
    public function isActive(array $deviceData): bool
    {
        $lastActivity = $this->getLastActivity($deviceData);
        // Consider active if activity within last 7 days
        return (time() - $lastActivity) < (7 * 24 * 60 * 60);
    }

    /**
     * Get last activity timestamp
     */
    public function getLastActivity(array $deviceData): int
    {
        return (int)($deviceData['last_activity'] ?? time());
    }

    /**
     * Validate device data
     */
    public function validateDeviceData(array $deviceData): bool
    {
        return isset($deviceData['id']) && isset($deviceData['name']);
    }

    /**
     * Get driver priority (default: 100)
     */
    public function getPriority(): int
    {
        return 100;
    }

    /**
     * Log driver operation
     */
    protected function logOperation(string $operation, array $context = []): void
    {
        $this->logger->debug("Driver {$this->getName()}: $operation", [
            'driver' => $this->getName(),
            'operation' => $operation,
            'context' => $context
        ]);
    }

    /**
     * Log driver error
     */
    protected function logError(string $operation, \Exception $e, array $context = []): void
    {
        $this->logger->error("Driver {$this->getName()} error", [
            'driver' => $this->getName(),
            'operation' => $operation,
            'error' => $e->getMessage(),
            'context' => $context,
            'trace' => $e->getTraceAsString()
        ]);
    }
}