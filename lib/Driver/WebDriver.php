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
 * Web browser device driver
 */
class WebDriver extends BaseDriver
{
    private const BROWSERS = [
        'chrome',
        'firefox',
        'safari',
        'edge',
        'opera',
        'browser'
    ];

    public function getName(): string
    {
        return 'Web';
    }

    public function getPriority(): int
    {
        return 100;
    }

    public function supports(array $deviceData): bool
    {
        $name = strtolower($deviceData['name'] ?? '');
        
        // Check if it's a web browser
        foreach (self::BROWSERS as $browser) {
            if (strpos($name, $browser) !== false) {
                return true;
            }
        }

        // Check device type
        $deviceType = strtolower($deviceData['device_type'] ?? '');
        return $deviceType === 'web' || $deviceType === 'browser';
    }

    public function supportsRemoteWipe(): bool
    {
        // Web browsers typically don't support remote wipe
        return false;
    }

    public function remoteWipe(int $tokenId, array $deviceData): bool
    {
        // Web browsers don't support remote wipe
        $this->logger->warning('Web browser does not support remote wipe', [
            'token_id' => $tokenId,
            'device_name' => $deviceData['name'] ?? 'unknown'
        ]);
        return false;
    }

    public function getDeviceInfo(array $deviceData): array
    {
        $info = [
            'type' => 'web',
            'browser' => 'unknown',
            'version' => 'unknown',
            'name' => $deviceData['name'] ?? 'Web Browser',
            'last_activity' => $this->getLastActivity($deviceData),
            'is_active' => $this->isActive($deviceData),
        ];

        // Detect browser
        $name = strtolower($deviceData['name'] ?? '');
        foreach (self::BROWSERS as $browser) {
            if (strpos($name, $browser) !== false) {
                $info['browser'] = $browser;
                break;
            }
        }

        // Detect version
        if (preg_match('/(\d+\.\d+\.\d+|\d+\.\d+)/i', $name, $matches)) {
            $info['version'] = $matches[1];
        }

        return $info;
    }

    public function validateDeviceData(array $deviceData): bool
    {
        return parent::validateDeviceData($deviceData) &&
            (!isset($deviceData['device_type']) || $deviceData['device_type'] === 'web');
    }
}