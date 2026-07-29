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

namespace OCA\AdminOffboard\Validator;

use OCA\AdminOffboard\Adapter\NextcloudAdapter;
use OCA\AdminOffboard\Db\Repository\DeviceRepository;
use OCA\AdminOffboard\Exception\ValidationException;

/**
 * Device validator
 */
class DeviceValidator
{
    public function __construct(
        private NextcloudAdapter $adapter,
        private DeviceRepository $repository
    ) {
    }

    /**
     * Validate device exists
     *
     * @throws ValidationException
     */
    public function validateDeviceExists(int $deviceId): void
    {
        $device = $this->repository->find($deviceId);
        if (!$device) {
            throw new ValidationException("Device '$deviceId' does not exist");
        }
    }

    /**
     * Validate device belongs to user
     *
     * @throws ValidationException
     */
    public function validateDeviceBelongsToUser(int $deviceId, string $userId): void
    {
        $device = $this->repository->find($deviceId);
        if (!$device) {
            throw new ValidationException("Device '$deviceId' does not exist");
        }

        if ($device->getUserId() !== $userId) {
            throw new ValidationException("Device '$deviceId' does not belong to user '$userId'");
        }
    }

    /**
     * Validate device supports remote wipe
     *
     * @throws ValidationException
     */
    public function validateDeviceSupportsWipe(int $deviceId): void
    {
        $device = $this->repository->find($deviceId);
        if (!$device) {
            throw new ValidationException("Device '$deviceId' does not exist");
        }

        if (!$device->isWipeSupported()) {
            throw new ValidationException("Device '$deviceId' does not support remote wipe");
        }
    }

    /**
     * Validate device is active
     *
     * @throws ValidationException
     */
    public function validateDeviceActive(int $deviceId): void
    {
        $device = $this->repository->find($deviceId);
        if (!$device) {
            throw new ValidationException("Device '$deviceId' does not exist");
        }

        if (!$device->isActive()) {
            throw new ValidationException("Device '$deviceId' is not active");
        }
    }

    /**
     * Validate user has devices
     *
     * @throws ValidationException
     */
    public function validateUserHasDevices(string $userId): void
    {
        $devices = $this->adapter->getUserDevices($userId);
        if (empty($devices)) {
            throw new ValidationException("User '$userId' has no devices");
        }
    }

    /**
     * Validate user has wipe-capable devices
     *
     * @throws ValidationException
     */
    public function validateUserHasWipeCapableDevices(string $userId): void
    {
        $devices = $this->adapter->getUserDevices($userId);
        $wipeCapable = array_filter($devices, function ($device) {
            return $device->isWipeSupported();
        });

        if (empty($wipeCapable)) {
            throw new ValidationException("User '$userId' has no devices that support remote wipe");
        }
    }
}