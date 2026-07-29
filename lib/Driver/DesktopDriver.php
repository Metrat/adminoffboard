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
 * Desktop device driver
 */
class DesktopDriver extends BaseDriver
{
    private const SUPPORTED_PLATFORMS = [
        'windows',
        'macos',
        'linux',
        'desktop'
    ];

    private const WIPE_CAPABLE_CLIENTS = [
        'nextcloud-desktop',
        'windows-desktop',
        'macos-desktop',
        'linux-desktop'
    ];

    public function getName(): string
    {
        return 'Desktop';
    }

    public function getPriority(): int
    {
        return 200;
    }

    public function supports(array $deviceData): bool
    {
        $name = strtolower($deviceData['name'] ?? '');
        
        // Check if it's a desktop device
        foreach (self::SUPPORTED_PLATFORMS as $platform) {
            if (strpos($name, $platform) !== false) {
                return true;
            }
        }

        // Check device type if available
        $deviceType = strtolower($deviceData['device_type'] ?? '');
        return in_array($deviceType, ['desktop', 'pc', 'workstation', 'laptop']);
    }

    public function supportsRemoteWipe(): bool
    {
        return true;
    }

    public function remoteWipe(int $tokenId, array $deviceData): bool
    {
        $this->logOperation('remote_wipe', [
            'token_id' => $tokenId,
            'device_name' => $deviceData['name'] ?? 'unknown'
        ]);

        try {
            // Check if this client supports remote wipe
            $name = strtolower($deviceData['name'] ?? '');
            $supported = false;

            foreach (self::WIPE_CAPABLE_CLIENTS as $client) {
                if (strpos($name, $client) !== false) {
                    $supported = true;
                    break;
                }
            }

            if (!$supported) {
                $this->logger->warning('Desktop client does not support remote wipe', [
                    'device_name' => $name
                ]);
                return false;
            }

            // Perform remote wipe by deleting the token
            // For desktop clients, we also need to send a wipe command
            // This is handled by the Nextcloud server
            $deleted = $this->tokenAdapter->deleteToken($tokenId);

            if ($deleted) {
                $this->logOperation('remote_wipe_success', [
                    'token_id' => $tokenId
                ]);
            }

            return $deleted;
        } catch (\Exception $e) {
            $this->logError('remote_wipe', $e, [
                'token_id' => $tokenId
            ]);
            return false;
        }
    }

    public function getDeviceInfo(array $deviceData): array
    {
        $info = [
            'type' => 'desktop',
            'platform' => 'unknown',
            'version' => 'unknown',
            'name' => $deviceData['name'] ?? 'Unknown Desktop',
            'last_activity' => $this->getLastActivity($deviceData),
            'is_active' => $this->isActive($deviceData),
        ];

        // Try to detect platform
        $name = strtolower($deviceData['name'] ?? '');
        foreach (self::SUPPORTED_PLATFORMS as $platform) {
            if (strpos($name, $platform) !== false) {
                $info['platform'] = $platform;
                break;
            }
        }

        // Detect client version
        if (preg_match('/v?(\d+\.\d+\.\d+)/i', $name, $matches)) {
            $info['version'] = $matches[1];
        }

        return $info;
    }

    public function validateDeviceData(array $deviceData): bool
    {
        return parent::validateDeviceData($deviceData) &&
            isset($deviceData['device_type']) &&
            $deviceData['device_type'] === 'desktop';
    }
}