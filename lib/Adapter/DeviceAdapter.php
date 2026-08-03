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

namespace OCA\AdminOffboard\Adapter;

use OCA\AdminOffboard\Db\Entity\Device;
use OCA\AdminOffboard\Db\Repository\DeviceRepository;

/**
 * Device management adapter
 */
class DeviceAdapter
{
    /**
     * Known device types that support remote wipe
     */
    private const WIPE_SUPPORTED_DEVICES = [
        'nextcloud-desktop',
        'nextcloud-mobile-ios',
        'nextcloud-mobile-android',
        'nextcloud-mobile',
        'windows-desktop',
        'macos-desktop',
        'linux-desktop'
    ];

    public function __construct(
        private DeviceRepository $deviceRepository,
        private TokenAdapter $tokenAdapter
    ) {
    }

    /**
     * Get user devices
     */

    /**
     * Remote wipe all devices for a user
     */
    public function remoteWipeUserDevices(string $userId): int
    {
        // Placeholder - needs implementation
        return (int) $this->remoteWipeUser($userId);
    }
    public function getUserDevices(string $userId): array
    {
        // Get cached devices from repository
        $devices = $this->deviceRepository->findByUser($userId);

        // If no cached devices, sync from tokens
        if (empty($devices)) {
            return $this->syncUserDevices($userId);
        }

        return $devices;
    }

    /**
     * Get device by ID
     */
    public function getDevice(int $deviceId): ?Device
    {
        return $this->deviceRepository->find($deviceId);
    }

    /**
     * Sync devices for a user from token data
     */
    public function syncUserDevices(string $userId): array
    {
        $tokens = $this->tokenAdapter->getUserTokens($userId);
        $devices = [];

        foreach ($tokens as $token) {
            $device = $this->createOrUpdateDevice($userId, $token);
            if ($device) {
                $devices[] = $device;
            }
        }

        return $devices;
    }

    /**
     * Create or update device from token data
     */
    private function createOrUpdateDevice(string $userId, array $tokenData): ?Device
    {
        $tokenId = (int)$tokenData['id'];
        
        // Check if device exists
        $existing = $this->deviceRepository->findByUserAndToken($userId, $tokenId);
        
        if ($existing) {
            // Update existing device
            $existing->setDeviceName($tokenData['name'] ?? 'Unknown Device');
            $existing->setLastActivity($tokenData['last_activity'] ?? time());
            $existing->setUpdatedAt(time());
            return $this->deviceRepository->update($existing);
        }

        // Create new device
        $device = new Device();
        $device->setUserId($userId);
        $device->setTokenId($tokenId);
        $device->setDeviceName($tokenData['name'] ?? 'Unknown Device');
        $device->setDeviceType($this->detectDeviceType($tokenData));
        $device->setLastActivity($tokenData['last_activity'] ?? time());
        $device->setWipeSupported($this->isWipeSupported($tokenData));
        $device->setCreatedAt(time());
        $device->setUpdatedAt(time());

        return $this->deviceRepository->create($device);
    }

    /**
     * Detect device type from token data
     */
    private function detectDeviceType(array $tokenData): string
    {
        $name = strtolower($tokenData['name'] ?? '');
        
        if (strpos($name, 'desktop') !== false) {
            return 'desktop';
        }
        
        if (strpos($name, 'mobile') !== false || strpos($name, 'phone') !== false) {
            return 'mobile';
        }
        
        if (strpos($name, 'browser') !== false || strpos($name, 'web') !== false) {
            return 'web';
        }
        
        return 'unknown';
    }

    /**
     * Check if device supports remote wipe
     */
    private function isWipeSupported(array $tokenData): bool
    {
        $name = strtolower($tokenData['name'] ?? '');
        
        foreach (self::WIPE_SUPPORTED_DEVICES as $supported) {
            if (strpos($name, $supported) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Remote wipe all devices for a user
     */
    public function remoteWipeUser(string $userId, ?string $deviceId = null): bool
    {
        if ($deviceId) {
            // Wipe specific device
            $device = $this->deviceRepository->findByUserAndDeviceId($userId, $deviceId);
            if ($device) {
                return $this->remoteWipeDevice($device->getId());
            }
            return false;
        }

        // Wipe all devices
        $devices = $this->getUserDevices($userId);
        $success = true;

        foreach ($devices as $device) {
            if ($device->isWipeSupported()) {
                $result = $this->remoteWipeDevice($device->getId());
                if (!$result) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Remote wipe a specific device
     */
    public function remoteWipeDevice(int $deviceId): bool
    {
        $device = $this->deviceRepository->find($deviceId);
        if (!$device || !$device->isWipeSupported()) {
            return false;
        }

        // Delete the device token (effectively wiping it)
        $deleted = $this->tokenAdapter->deleteToken($device->getTokenId());
        
        if ($deleted) {
            $device->setWipeSupported(false); // Device is now wiped
            $this->deviceRepository->update($device);
            return true;
        }

        return false;
    }

    /**
     * Check if remote wipe is supported for device
     */
    public function isRemoteWipeSupported(string $userId, int $tokenId): bool
    {
        $device = $this->deviceRepository->findByUserAndToken($userId, $tokenId);
        return $device ? $device->isWipeSupported() : false;
    }
}