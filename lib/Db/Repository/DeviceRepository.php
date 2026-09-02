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

namespace OCA\AdminOffboard\Db\Repository;

use OCA\AdminOffboard\Db\Entity\Device;
use OCA\AdminOffboard\Db\Mapper\DeviceMapper;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Repository for Device entities
 */
class DeviceRepository
{
    public function __construct(
        private DeviceMapper $mapper
    ) {
    }

    /**
     * Create a new device
     */
    public function create(Device $device): Device
    {
        return $this->mapper->insert($device);
    }

    /**
     * Update a device
     */
    public function update(Device $device): Device
    {
        return $this->mapper->update($device);
    }

    /**
     * Find device by ID
     *
     * @throws DoesNotExistException
     */
    public function find(int $id): Device
    {
        return $this->mapper->find($id);
    }

    /**
     * Find devices by user
     */
    public function findByUser(string $userId): array
    {
        return $this->mapper->findByUserId($userId);
    }

    /**
     * Find device by user and token
     */
    public function findByUserAndToken(string $userId, int $tokenId): ?Device
    {
        try {
            return $this->mapper->findByUserAndToken($userId, $tokenId);
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * Find device by user and device ID
     */
    public function findByUserAndDeviceId(string $userId, string $deviceId): ?Device
    {
        try {
            return $this->mapper->findByUserAndDeviceId($userId, $deviceId);
        } catch (DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * Find devices by type
     */
    public function findByType(string $deviceType): array
    {
        return $this->mapper->findByType($deviceType);
    }

    /**
     * Find devices that support remote wipe
     */
    public function findWipeSupported(): array
    {
        return $this->mapper->findWipeSupported();
    }

    /**
     * Delete a device
     */
    public function delete(Device $device): void
    {
        $this->mapper->delete($device);
    }

    /**
     * Delete devices by user
     */
    public function deleteByUser(string $userId): int
    {
        return $this->mapper->deleteByUser($userId);
    }

    /**
     * Delete devices older than specified date
     */
    public function deleteOldDevices(int $timestamp): int
    {
        return $this->mapper->deleteOldDevices($timestamp);
    }

    /**
     * Get device count by user
     */
    public function countByUser(string $userId): int
    {
        return $this->mapper->countByUser($userId);
    }

    /**
     * Get device statistics
     */
    public function getStats(): array
    {
        return [
            'total' => $this->mapper->countAll(),
            'by_type' => $this->getTypeStats(),
            'wipe_supported' => $this->mapper->countWipeSupported(),
            'active' => $this->mapper->countActive(),
        ];
    }

    /**
     * Get device type statistics
     */
    private function getTypeStats(): array
    {
        $types = ['desktop', 'mobile', 'web', 'unknown'];
        $stats = [];
        foreach ($types as $type) {
            $stats[$type] = $this->mapper->countByType($type);
        }
        return $stats;
    }
}