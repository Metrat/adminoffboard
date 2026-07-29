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
 * Fallback driver for unknown devices
 */
class UnknownDriver extends BaseDriver
{
    public function getName(): string
    {
        return 'Unknown';
    }

    public function getPriority(): int
    {
        return 0; // Lowest priority
    }

    public function supports(array $deviceData): bool
    {
        // Supports all devices as fallback
        return true;
    }

    public function supportsRemoteWipe(): bool
    {
        return false;
    }

    public function remoteWipe(int $tokenId, array $deviceData): bool
    {
        $this->logger->warning('Remote wipe not supported for unknown device', [
            'token_id' => $tokenId,
            'device_name' => $deviceData['name'] ?? 'unknown'
        ]);
        return false;
    }

    public function getDeviceInfo(array $deviceData): array
    {
        return [
            'type' => 'unknown',
            'name' => $deviceData['name'] ?? 'Unknown Device',
            'last_activity' => $this->getLastActivity($deviceData),
            'is_active' => $this->isActive($deviceData),
        ];
    }

    public function validateDeviceData(array $deviceData): bool
    {
        return true;
    }
}