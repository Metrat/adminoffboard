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

/**
 * Driver interface for device operations
 */
interface DriverInterface
{
    /**
     * Get the driver name
     */
    public function getName(): string;

    /**
     * Get the driver version
     */
    public function getVersion(): string;

    /**
     * Check if this driver supports a specific device
     */
    public function supports(array $deviceData): bool;

    /**
     * Get device capabilities
     */
    public function getCapabilities(): array;

    /**
     * Check if remote wipe is supported
     */
    public function supportsRemoteWipe(): bool;

    /**
     * Perform remote wipe on device
     */
    public function remoteWipe(int $tokenId, array $deviceData): bool;

    /**
     * Get device information
     */
    public function getDeviceInfo(array $deviceData): array;

    /**
     * Check if device is active
     */
    public function isActive(array $deviceData): bool;

    /**
     * Get last activity timestamp
     */
    public function getLastActivity(array $deviceData): int;

    /**
     * Validate device data
     */
    public function validateDeviceData(array $deviceData): bool;

    /**
     * Get driver priority (higher = more specific)
     */
    public function getPriority(): int;
}