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

use OCA\AdminOffboard\Driver\Exception\DriverNotFoundException;
use OCA\AdminOffboard\Logger\AppLogger;

/**
 * Registry of all drivers
 */
class DriverRegistry
{
    /** @var DriverInterface[] */
    private array $drivers = [];

    public function __construct(
        private AppLogger $logger
    ) {
    }

    /**
     * Register a driver
     */
    public function registerDriver(DriverInterface $driver): void
    {
        $this->drivers[] = $driver;
        $this->logger->debug('Driver registered', [
            'driver' => $driver->getName(),
            'version' => $driver->getVersion()
        ]);
    }

    /**
     * Register multiple drivers
     */
    public function registerDrivers(array $drivers): void
    {
        foreach ($drivers as $driver) {
            if ($driver instanceof DriverInterface) {
                $this->registerDriver($driver);
            }
        }
    }

    /**
     * Get all registered drivers
     */
    public function getDrivers(): array
    {
        // Sort by priority (higher first)
        usort($this->drivers, function ($a, $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        return $this->drivers;
    }

    /**
     * Find a driver for a device
     */
    public function findDriver(array $deviceData): ?DriverInterface
    {
        $this->logger->debug('Finding driver for device', [
            'device_name' => $deviceData['name'] ?? 'unknown'
        ]);

        foreach ($this->getDrivers() as $driver) {
            if ($driver->supports($deviceData)) {
                $this->logger->debug('Driver found', [
                    'driver' => $driver->getName(),
                    'priority' => $driver->getPriority()
                ]);
                return $driver;
            }
        }

        $this->logger->warning('No driver found for device', [
            'device_name' => $deviceData['name'] ?? 'unknown'
        ]);
        return null;
    }

    /**
     * Get driver by name
     */
    public function getDriverByName(string $name): ?DriverInterface
    {
        foreach ($this->getDrivers() as $driver) {
            if (strtolower($driver->getName()) === strtolower($name)) {
                return $driver;
            }
        }
        return null;
    }

    /**
     * Check if a driver exists for a device
     */
    public function hasDriver(array $deviceData): bool
    {
        return $this->findDriver($deviceData) !== null;
    }

    /**
     * Get all driver names
     */
    public function getDriverNames(): array
    {
        return array_map(function ($driver) {
            return $driver->getName();
        }, $this->getDrivers());
    }

    /**
     * Get drivers that support remote wipe
     */
    public function getRemoteWipeDrivers(): array
    {
        return array_filter($this->getDrivers(), function ($driver) {
            return $driver->supportsRemoteWipe();
        });
    }

    /**
     * Get driver capabilities summary
     */
    public function getCapabilitiesSummary(): array
    {
        $summary = [];
        foreach ($this->getDrivers() as $driver) {
            $summary[$driver->getName()] = $driver->getCapabilities();
        }
        return $summary;
    }
}