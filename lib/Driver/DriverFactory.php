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
use OCA\AdminOffboard\Driver\Exception\DriverNotFoundException;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Factory for creating and managing drivers
 */
class DriverFactory
{
    private DriverRegistry $registry;

    public function __construct(
        private TokenAdapter $tokenAdapter,
        private AppLogger $logger
    ) {
        $this->initializeRegistry();
    }

    /**
     * Initialize the driver registry
     */
    private function initializeRegistry(): void
    {
        $this->registry = new DriverRegistry($this->logger);
        
        // Register all drivers
        $this->registry->registerDrivers([
            new DesktopDriver($this->tokenAdapter, $this->logger),
            new MobileDriver($this->tokenAdapter, $this->logger),
            new WebDriver($this->tokenAdapter, $this->logger),
            new UnknownDriver($this->tokenAdapter, $this->logger),
        ]);

        $this->logger->info('Driver registry initialized', [
            'drivers' => $this->registry->getDriverNames()
        ]);
    }

    /**
     * Get the driver registry
     */
    public function getRegistry(): DriverRegistry
    {
        return $this->registry;
    }

    /**
     * Get a driver for a device
     */
    public function getDriver(array $deviceData): DriverInterface
    {
        $driver = $this->registry->findDriver($deviceData);
        
        if ($driver === null) {
            // Use unknown driver as fallback
            $driver = $this->registry->getDriverByName('Unknown');
        }

        return $driver;
    }

    /**
     * Get driver by name
     */
    public function getDriverByName(string $name): DriverInterface
    {
        $driver = $this->registry->getDriverByName($name);
        
        if ($driver === null) {
            throw new DriverNotFoundException("Driver not found: $name");
        }

        return $driver;
    }

    /**
     * Check if device supports remote wipe
     */
    public function supportsRemoteWipe(array $deviceData): bool
    {
        $driver = $this->getDriver($deviceData);
        return $driver->supportsRemoteWipe();
    }

    /**
     * Perform remote wipe using appropriate driver
     */
    public function remoteWipe(array $deviceData): bool
    {
        $driver = $this->getDriver($deviceData);
        $tokenId = (int)($deviceData['id'] ?? 0);

        if (!$driver->supportsRemoteWipe()) {
            $this->logger->warning('Remote wipe not supported by driver', [
                'driver' => $driver->getName(),
                'device_name' => $deviceData['name'] ?? 'unknown'
            ]);
            return false;
        }

        return $driver->remoteWipe($tokenId, $deviceData);
    }

    /**
     * Get device information using appropriate driver
     */
    public function getDeviceInfo(array $deviceData): array
    {
        $driver = $this->getDriver($deviceData);
        return $driver->getDeviceInfo($deviceData);
    }

    /**
     * Get all driver capabilities
     */
    public function getCapabilities(): array
    {
        return $this->registry->getCapabilitiesSummary();
    }

    /**
     * Get drivers that support remote wipe
     */
    public function getRemoteWipeDrivers(): array
    {
        return $this->registry->getRemoteWipeDrivers();
    }
}