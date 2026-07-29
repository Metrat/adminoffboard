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
 * Mobile device driver
 */
class MobileDriver extends BaseDriver
{
    private const MOBILE_PLATFORMS = [
        'android',
        'ios',
        'iphone',
        'ipad',
        'mobile'
    ];

    private const WIPE_CAPABLE_CLIENTS = [
        'nextcloud-mobile',
        'nextcloud-android',
        'nextcloud-ios',
        'mobile-app'
    ];

    public function getName(): string
    {
        return 'Mobile';
    }

    public function getPriority(): int
    {
        return 300;
    }

    public function supports(array $deviceData): bool
    {
        $name = strtolower($deviceData['name'] ?? '');
        
        // Check if it's a mobile device
        foreach (self::MOBILE_PLATFORMS as $platform) {
            if (strpos($name, $platform) !== false) {
                return true;
            }
        }

        // Check device type
        $deviceType = strtolower($deviceData['device_type'] ?? '');
        return in_array($deviceType, ['mobile', 'phone', 'tablet']);
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
            // Check if this mobile client supports remote wipe
            $name = strtolower($deviceData['name'] ?? '');
            $supported = false;

            foreach (self::WIPE_CAPABLE_CLIENTS as $client) {
                if (strpos($name, $client) !== false) {
                    $supported = true;
                    break;
                }
            }

            if (!$supported) {
                $this->logger->warning('Mobile client does not support remote wipe', [
                    'device_name' => $name
                ]);
                return false;
            }

            // For mobile devices, we also need to send push notification
            // This is handled by the Nextcloud server
            $deleted = $this->tokenAdapter->deleteToken($tokenId);

            if ($deleted) {
                $this->logOperation('remote_wipe_success', [
                    'token_id' => $tokenId
                ]);
                
                // Trigger push notification for immediate wipe
                $this->triggerMobileWipe($deviceData);
            }

            return $deleted;
        } catch (\Exception $e) {
            $this->logError('remote_wipe', $e, [
                'token_id' => $tokenId
            ]);
            return false;
        }
    }

    /**
     * Trigger mobile wipe push notification
     */
    private function triggerMobileWipe(array $deviceData): void
    {
        // This would integrate with Nextcloud's push notification service
        // For now, we just log it
        $this->logger->info('Mobile wipe trigger requested', [
            'device_id' => $deviceData['id'] ?? 'unknown',
            'device_name' => $deviceData['name'] ?? 'unknown'
        ]);
    }

    public function getDeviceInfo(array $deviceData): array
    {
        $info = [
            'type' => 'mobile',
            'platform' => 'unknown',
            'model' => 'unknown',
            'name' => $deviceData['name'] ?? 'Unknown Mobile',
            'last_activity' => $this->getLastActivity($deviceData),
            'is_active' => $this->isActive($deviceData),
        ];

        // Detect platform
        $name = strtolower($deviceData['name'] ?? '');
        foreach (self::MOBILE_PLATFORMS as $platform) {
            if (strpos($name, $platform) !== false) {
                $info['platform'] = $platform;
                break;
            }
        }

        // Try to detect model
        if (preg_match('/(iphone|ipad|galaxy|pixel|oneplus)/i', $name, $matches)) {
            $info['model'] = $matches[1];
        }

        return $info;
    }

    public function validateDeviceData(array $deviceData): bool
    {
        return parent::validateDeviceData($deviceData) &&
            isset($deviceData['device_type']) &&
            in_array($deviceData['device_type'], ['mobile', 'phone', 'tablet']);
    }
}